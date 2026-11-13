<?php

namespace App\Actions\Server;

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\MigrationDisposition;
use App\Enums\Server\MigrationTransport;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ProgressMode;
use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Proxmox\NextVMIDRetrievalException;
use App\Exceptions\Service\Server\Allocation\NoUniqueVmidException;
use App\Exceptions\Service\Server\MigrationRefusedException;
use App\Jobs\Server\CommitServerMigrationJob;
use App\Jobs\Server\DestroySourceGuestJob;
use App\Jobs\Server\DiscardMigrationArtifactJob;
use App\Jobs\Server\ExportGuestJob;
use App\Jobs\Server\InstallMigratedGuestJob;
use App\Jobs\Server\MigrateVmJob;
use App\Jobs\Server\SendPowerCommandJob;
use App\Jobs\Server\StopVmJob;
use App\Jobs\Server\VerifyMigratedGuestJob;
use App\Models\Address;
use App\Models\Deployment;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\ServerMigrationTransfer;
use App\Models\Storage;
use App\Services\Proxmox\Node\ProxmoxAllocationClient;
use App\Services\Servers\AnchorMigrationRollbackService;
use App\Services\Servers\ServerCreationService;
use App\Services\Servers\ServerMigrationService;
use App\Traits\Actions\ManagesDeploymentLifecycle;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Throwable;

use function now;

/**
 * Builds and dispatches the chain that moves a server to another node.
 *
 * The order of writes is the whole design, and it is Neutron's: reserve the
 * destination binding, issue the task, commit only on success. Nothing about
 * the server's addresses changes before PVE confirms the guest landed, and the
 * addresses it is giving up stay assigned to it until then. A migration that
 * fails leaves a row describing where the guest still is.
 *
 * Preserve is one remote step. Reallocate is four, because a new address only
 * reaches a guest through cloud-init: stop it, move it offline (PVE migrates a
 * stopped guest to a node without the bridge quite happily, it just will not
 * start it there), rewrite the NIC and `ipconfig0` on the destination, then put
 * it back the way it was found.
 *
 * A destination outside the source's cluster takes the Anchor transport
 * instead, which is a longer chain around the same rule. Its ordering is fixed
 * by docs/migration-anchor-contract.md and the invariant is one sentence: the
 * source guest is not destroyed until the destination guest is verified. Every
 * step before that is undone by restarting a guest nobody touched, which is
 * why {@see AnchorMigrationRollbackService} never has to restore anything.
 */
class MigrateServerAction
{
    use ManagesDeploymentLifecycle;

    public function __construct(
        private ServerMigrationService $migration,
        private ConnectionInterface $connection,
        private ProxmoxAllocationClient $allocation,
        private ServerCreationService $creation,
    ) {}

    /**
     * @throws MigrationRefusedException
     */
    public function execute(Server $server, Node $target, bool $acknowledged): Deployment
    {
        [$candidate, $interface, $wasRunning] = $this->migration->resolve($server, $target);

        if ($candidate->disposition === MigrationDisposition::Blocked) {
            throw new MigrationRefusedException(
                $candidate->blockedReason ?? 'This server can no longer be migrated to that node.',
            );
        }

        if ($candidate->disposition === MigrationDisposition::Reallocate && ! $acknowledged) {
            throw new MigrationRefusedException(
                'This server would get new IP addresses on that node. Confirm the address change before migrating.',
            );
        }

        if ($interface === null && $server->addresses()->exists()) {
            throw new MigrationRefusedException(
                sprintf('%s no longer has a network interface that can carry this server\'s addresses.', $target->name),
            );
        }

        $anchor = $candidate->transport === MigrationTransport::Anchor;

        // Refused again here, not only in the plan: the dialog's verdict can
        // be minutes old, and an Anchor that went away in between is a
        // migration that stops the guest and then cannot move it.
        if ($anchor) {
            $refusal = $this->migration->anchorRefusal($server, $target);

            if ($refusal !== null) {
                throw new MigrationRefusedException($refusal);
            }
        }

        // Allocated before the transaction: it is a Proxmox round trip, and
        // holding a write transaction open across the network is how a slow
        // node becomes a lock nobody can explain.
        $placement = $anchor ? $this->destinationPlacement($server, $target) : null;

        /**
         * The chain is dispatched *after* the transaction, not inside it.
         *
         * A queue driver that runs a job inline -- `sync`, and the test suite
         * -- would otherwise run the whole migration inside this transaction,
         * and a failure anywhere in it would roll back the very rows the
         * failure is supposed to leave behind: the deployment, the failed
         * lifecycle, the record of what happened. The transaction's job is to
         * make the reservation and the deployment atomic with each other, and
         * that job is finished before anything is issued.
         */
        [$deployment, $dispatch] = $this->connection->transaction(function () use (
            $server,
            $target,
            $candidate,
            $interface,
            $wasRunning,
            $anchor,
            $placement,
        ) {
            [$reserved, $released] = $candidate->disposition === MigrationDisposition::Reallocate
                ? $this->reserveDestination($server, $interface)
                : [[], []];

            $deployment = $server->deployments()->create([
                'type' => DeploymentType::MIGRATE,
                'status' => DeploymentStatus::PENDING,
                'start_on_completion' => false,
                'requested_at' => now(),
            ]);

            $server->update(['lifecycle' => ServerLifecycle::MIGRATING]);

            if (! $anchor) {
                return [$deployment, fn () => Bus::chain(Arr::flatten([
                    $this->onStart($deployment),
                    $this->jobsFor($deployment, $target, $candidate->disposition, $interface, $reserved, $released, $wasRunning),
                    $this->onComplete($deployment),
                ]))
                    ->catch($this->onFail($deployment, ServerLifecycle::MIGRATION_FAILED))
                    ->dispatch()];
            }

            /** @var array{int, Storage} $placement */
            [$vmid, $storage] = $placement;

            $transfer = ServerMigrationTransfer::create([
                'deployment_id' => $deployment->id,
                'server_id' => $server->id,
                'source_node_id' => $server->node_id,
                'destination_node_id' => $target->id,
                'source_vmid' => $server->vmid,
                'destination_vmid' => $vmid,
                'destination_storage' => $storage->name,
                'was_running' => $wasRunning,
            ]);

            return [$deployment, fn () => Bus::chain(Arr::flatten([
                $this->onStart($deployment),
                $this->anchorJobsFor($deployment, $transfer, $candidate->disposition, $interface, $reserved, $released, $storage, $wasRunning),
                $this->onComplete($deployment),
            ]))
                ->catch($this->onAnchorFail($deployment, $transfer, $reserved))
                ->dispatch()];
        });

        $dispatch();

        return $deployment;
    }

    /**
     * Where the guest lands on the destination: a free VMID and the storage
     * its disks are restored onto.
     *
     * The VMID is the destination's to choose, because the two nodes number
     * their guests independently and both running a guest 100 is the normal
     * case rather than an edge one. Keeping the current VMID when it happens
     * to be free is worth the one extra call: an operator who has the number
     * written down somewhere keeps it, and a renumbering nobody asked for is a
     * small betrayal of that.
     *
     * @return array{int, Storage}
     *
     * @throws MigrationRefusedException
     */
    private function destinationPlacement(Server $server, Node $target): array
    {
        $storage = $this->migration->destinationStorage($server, $target);

        if ($storage === null) {
            throw new MigrationRefusedException(sprintf(
                '%s has no storage named "%s" for the guest\'s disks to be restored onto.',
                $target->name,
                $server->storage->name,
            ));
        }

        try {
            $keepsVmid = Server::isUniqueVmId($target, $server->vmid)
                && $this->allocation->setNode($target)->isVMIDAvailable($server->vmid);

            $vmid = $keepsVmid ? $server->vmid : $this->creation->generateUniqueVmId($target);
        } catch (NextVMIDRetrievalException|NoUniqueVmidException $exception) {
            throw new MigrationRefusedException(sprintf(
                '%s could not give this guest a VMID to land on.',
                $target->name,
            ));
        }

        return [$vmid, $storage];
    }

    /**
     * Take the destination addresses out of the pool before anything is issued,
     * and note which ones the server gives up. Reserved rather than assigned:
     * the server holds its current addresses until the guest has landed, and a
     * row that is `assigned` to two sets of addresses at once would be read as
     * one by every consumer of `$server->addresses`.
     *
     * @return array{array<int, int>, array<int, int>} reserved ids, released ids
     */
    private function reserveDestination(Server $server, NetworkInterface $interface): array
    {
        $released = $server->addresses()->pluck('id')->all();

        $reserved = $this->migration->allocateReplacements($server, $interface);

        Address::query()
            ->whereIn('id', $reserved->pluck('id'))
            ->update([
                'state' => AddressState::Reserved,
                'state_reason' => AddressStateReason::Migration,
            ]);

        return [$reserved->pluck('id')->all(), $released];
    }

    /**
     * @param  array<int, int>  $reserved
     * @param  array<int, int>  $released
     * @return array<int, object>
     */
    private function jobsFor(
        Deployment $deployment,
        Node $target,
        MigrationDisposition $disposition,
        ?NetworkInterface $interface,
        array $reserved,
        array $released,
        bool $wasRunning,
    ): array {
        $preserve = $disposition === MigrationDisposition::Preserve;

        // Online migration is PVE's to decide: it ignores the flag for a
        // stopped guest. It is only ever asked for when the addresses follow.
        $online = $preserve && $wasRunning;

        $rows = [];

        if (! $preserve && $wasRunning) {
            $rows[] = ['name' => 'stop-vm', 'status' => DeploymentStatus::PENDING, 'progress_mode' => ProgressMode::INDETERMINATE];
        }

        $rows[] = ['name' => 'migrate-vm', 'status' => DeploymentStatus::PENDING, 'progress_mode' => ProgressMode::INDETERMINATE];
        $rows[] = ['name' => 'rebind-network', 'status' => DeploymentStatus::PENDING, 'progress_mode' => ProgressMode::INDETERMINATE];

        if (! $preserve && $wasRunning) {
            $rows[] = ['name' => 'start-vm', 'status' => DeploymentStatus::PENDING, 'progress_mode' => ProgressMode::INDETERMINATE];
        }

        $steps = $deployment->addSteps($rows)->values();
        $jobs = [];
        $cursor = 0;

        if (! $preserve && $wasRunning) {
            $jobs[] = new StopVmJob($steps[$cursor++]);
        }

        $jobs[] = new MigrateVmJob($steps[$cursor++], $target->id, $online);
        $jobs[] = new CommitServerMigrationJob(
            $steps[$cursor++],
            $target->id,
            $interface?->id,
            $disposition,
            $reserved,
            $released,
        );

        if (! $preserve && $wasRunning) {
            $jobs[] = new SendPowerCommandJob($steps[$cursor], PowerCommand::START);
        }

        return $jobs;
    }

    /**
     * The Anchor chain, in the order docs/migration-anchor-contract.md fixes.
     *
     * Read it as two halves either side of `verify-guest`. Before it, nothing
     * has happened to the source guest but a stop, so any failure is undone by
     * starting it again. After it, the destination is known good and the chain
     * is committing to it. There is no step that is reversible only sometimes,
     * which is the property that makes the rollback a single service rather
     * than a per-step unwind.
     *
     * `discard-artifact` sits on the reversible side although the contract
     * lists it last; {@see DiscardMigrationArtifactJob} explains why.
     *
     * @param  array<int, int>  $reserved
     * @param  array<int, int>  $released
     * @return array<int, object>
     */
    private function anchorJobsFor(
        Deployment $deployment,
        ServerMigrationTransfer $transfer,
        MigrationDisposition $disposition,
        ?NetworkInterface $interface,
        array $reserved,
        array $released,
        Storage $storage,
        bool $wasRunning,
    ): array {
        $indeterminate = fn (string $name) => [
            'name' => $name,
            'status' => DeploymentStatus::PENDING,
            'progress_mode' => ProgressMode::INDETERMINATE,
        ];

        // The two that move bytes report how many; the rest are remote calls
        // with nothing meaningful to count.
        $determinate = fn (string $name) => [
            'name' => $name,
            'status' => DeploymentStatus::PENDING,
            'progress_mode' => ProgressMode::DETERMINATE,
        ];

        $rows = [];

        // Unconditional, unlike the cluster path: the archive has to be taken
        // from a guest that is not writing to its disks.
        if ($wasRunning) {
            $rows[] = $indeterminate('stop-vm');
        }

        $rows[] = $determinate('export-guest');
        $rows[] = $determinate('install-guest');
        $rows[] = $indeterminate('verify-guest');
        $rows[] = $indeterminate('discard-artifact');
        $rows[] = $indeterminate('destroy-source');
        $rows[] = $indeterminate('rebind-network');

        if ($wasRunning) {
            $rows[] = $indeterminate('start-vm');
        }

        $steps = $deployment->addSteps($rows)->values();
        $jobs = [];
        $cursor = 0;

        if ($wasRunning) {
            $jobs[] = new StopVmJob($steps[$cursor++]);
        }

        $jobs[] = new ExportGuestJob($steps[$cursor++], $transfer->id);
        $jobs[] = new InstallMigratedGuestJob($steps[$cursor++], $transfer->id);
        $jobs[] = new VerifyMigratedGuestJob($steps[$cursor++], $transfer->id);
        $jobs[] = new DiscardMigrationArtifactJob($steps[$cursor++], $transfer->id);
        $jobs[] = new DestroySourceGuestJob($steps[$cursor++], $transfer->id);
        $jobs[] = new CommitServerMigrationJob(
            $steps[$cursor++],
            $transfer->destination_node_id,
            $interface?->id,
            $disposition,
            $reserved,
            $released,
            $transfer->destination_vmid,
            $storage->id,
        );

        if ($wasRunning) {
            $jobs[] = new SendPowerCommandJob($steps[$cursor], PowerCommand::START);
        }

        return $jobs;
    }

    /**
     * What a failed Anchor migration does on its way out.
     *
     * The rollback runs first and the deployment is marked failed second, so
     * the server is already back up by the time the UI says the migration
     * stopped. The rollback is written to be safe after the point of no
     * return -- it refuses to run once the transfer is verified -- because
     * this callback cannot see which step threw.
     *
     * @param  array<int, int>  $reserved
     */
    private function onAnchorFail(Deployment $deployment, ServerMigrationTransfer $transfer, array $reserved): callable
    {
        $fail = $this->onFail($deployment, ServerLifecycle::MIGRATION_FAILED);

        return function (Throwable $exception) use ($fail, $transfer, $reserved) {
            app(AnchorMigrationRollbackService::class)->rollback($transfer->refresh(), $reserved);

            $fail($exception);
        };
    }
}
