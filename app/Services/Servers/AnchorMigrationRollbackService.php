<?php

namespace App\Services\Servers;

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Server\PowerCommand;
use App\Models\Address;
use App\Models\ServerMigrationTransfer;
use App\Services\Anchor\AnchorMigrationClient;
use App\Services\Proxmox\Server\ProxmoxMigrationClient;
use App\Services\Proxmox\Server\ProxmoxPowerClient;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Undoes an Anchor migration that stopped part-way.
 *
 * There is nothing to restore. The source guest was stopped, copied and left
 * alone, so putting things back is: start it again if it was running, hand the
 * destination addresses back to the pool, and clean up whatever the attempt
 * left on the far side. That is the entire argument for the ordering in
 * docs/migration-anchor-contract.md, and it is why this service can be run
 * from any step before the destroy without knowing which one failed.
 *
 * Every remote call here is best-effort and independently guarded. A rollback
 * that aborts on its first unreachable node would leave the reservation held
 * and the guest down, which is strictly worse than a rollback that logs what
 * it could not reach and finishes the parts it can. The panel-side writes --
 * the reservation, the transfer row -- happen regardless, because those are
 * the ones nothing else will ever come back and fix.
 */
class AnchorMigrationRollbackService
{
    public function __construct(
        private AnchorMigrationClient $anchor,
        private ProxmoxMigrationClient $proxmox,
        private ProxmoxPowerClient $power,
    ) {}

    /**
     * @param  array<int, int>  $reservedAddressIds  destination addresses taken out of the pool up front
     */
    public function rollback(ServerMigrationTransfer $transfer, array $reservedAddressIds = []): void
    {
        // Once the source guest is destroyed there is nothing to go back to,
        // and "rolling back" would mean deleting the only remaining copy.
        // Note this is the destroy and not the verification: a failure in
        // between -- the artifact would not delete, say -- is still recovered
        // by going back, because the source is still there to go back to.
        if ($transfer->isCommitted() || $transfer->rolled_back_at !== null) {
            return;
        }

        $this->cancelRemoteJobs($transfer);
        $this->removeDestinationGuest($transfer);
        $this->discardArtifact($transfer);
        $this->releaseReservation($reservedAddressIds);
        $this->restartSource($transfer);

        $transfer->forceFill(['rolled_back_at' => now()])->save();
    }

    /**
     * Stop whatever is still running on either node before undoing its
     * effects. An export still writing to disk would otherwise keep producing
     * the artifact that is about to be discarded.
     */
    private function cancelRemoteJobs(ServerMigrationTransfer $transfer): void
    {
        if ($transfer->export_job_id !== null) {
            $this->attempt(
                fn () => $this->anchor->cancelExport($transfer->sourceNode, (string) $transfer->export_job_id),
                'cancel the export',
                $transfer,
            );
        }

        if ($transfer->install_job_id !== null) {
            $this->attempt(
                fn () => $this->anchor->cancelInstall($transfer->destinationNode, (string) $transfer->install_job_id),
                'cancel the install',
                $transfer,
            );
        }
    }

    /**
     * Remove anything the restore left at the destination VMID.
     *
     * Only ever attempted when an install was actually started: without that,
     * the VMID is one the panel picked and never used, and issuing a destroy
     * against it would be pointing a delete at a guest somebody else owns. Not
     * doing this is what leaves a retry with two guests.
     */
    private function removeDestinationGuest(ServerMigrationTransfer $transfer): void
    {
        if ($transfer->install_job_id === null) {
            return;
        }

        $this->attempt(function () use ($transfer) {
            $config = $this->proxmox->getGuestConfig($transfer->destinationNode, $transfer->destination_vmid);

            if ($config === []) {
                return;
            }

            $this->proxmox->destroyGuest($transfer->destinationNode, $transfer->destination_vmid);
        }, 'remove the partially restored guest', $transfer);
    }

    private function discardArtifact(ServerMigrationTransfer $transfer): void
    {
        if ($transfer->artifact === null) {
            return;
        }

        $this->attempt(
            fn () => $this->anchor->discard($transfer->sourceNode, (string) $transfer->artifact),
            'discard the export artifact',
            $transfer,
        );
    }

    /**
     * Hand the destination addresses back.
     *
     * Scoped to the migration reason so a rollback can never free an address
     * some other operation reserved in the meantime, and unconditional because
     * an address held out of the pool by a migration that is over is a leak
     * nothing else detects.
     *
     * @param  array<int, int>  $ids
     */
    private function releaseReservation(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        Address::query()
            ->whereIn('id', $ids)
            ->where('state_reason', AddressStateReason::Migration)
            ->update([
                'server_id' => null,
                'state' => AddressState::Available,
                'state_reason' => null,
            ]);
    }

    /**
     * Put the guest back the way it was found, which means leaving a guest
     * that was already stopped alone.
     */
    private function restartSource(ServerMigrationTransfer $transfer): void
    {
        if (! $transfer->was_running) {
            return;
        }

        $server = $transfer->server;

        $this->attempt(
            fn () => $this->power->setServer($server)->setNode($transfer->sourceNode)->send(PowerCommand::START),
            'restart the source guest',
            $transfer,
        );
    }

    private function attempt(callable $work, string $what, ServerMigrationTransfer $transfer): void
    {
        try {
            $work();
        } catch (Throwable $exception) {
            Log::warning('Could not '.$what.' while rolling back a migration.', [
                'transfer' => $transfer->id,
                'server' => $transfer->server_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
