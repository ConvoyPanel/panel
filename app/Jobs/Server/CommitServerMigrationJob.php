<?php

namespace App\Jobs\Server;

use App\Enums\Audit\AuditEvent;
use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Server\MigrationDisposition;
use App\Exceptions\Proxmox\RequestException;
use App\Facades\Audit;
use App\Models\Address;
use App\Models\DeploymentStep;
use App\Models\Node;
use App\Models\SystemActor;
use App\Services\Servers\ServerNetworkService;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Owns the `rebind` step: the panel-side half of a migration, run only after
 * PVE reports the guest has actually landed.
 *
 * Nothing here is speculative. The reservation made before the task was issued
 * is turned into an assignment, the addresses the server is giving up go back
 * to their pool, and `node_id` / `network_interface_id` follow. Doing it in this
 * order is the Neutron rule: the destination binding is created and validated
 * before the guest is committed to the move, and the source binding is not
 * released until the destination is confirmed.
 *
 * On a reallocation the guest's own config is rewritten too, because the new
 * address means nothing to it until `ipconfig0` and the NIC's bridge say so.
 * That write happens here rather than before the move for the obvious reason:
 * before the move it would have been written to the wrong node.
 */
class CommitServerMigrationJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
        public int $targetNodeId,
        public ?int $targetInterfaceId,
        public MigrationDisposition $disposition,
        /** @var array<int, int> addresses reserved on the destination before the task was issued */
        public array $reservedAddressIds = [],
        /** @var array<int, int> addresses the server gives up, released only now */
        public array $releasedAddressIds = [],
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ServerNetworkService $network): void
    {
        $this->step->markRunning();

        $server = $this->step->deployment->server;
        $target = Node::findOrFail($this->targetNodeId);
        $previous = $server->node;

        DB::transaction(function () use ($server, $target, $previous) {
            if ($this->releasedAddressIds !== []) {
                Address::query()
                    ->whereIn('id', $this->releasedAddressIds)
                    ->where('server_id', $server->id)
                    ->update([
                        'server_id' => null,
                        'state' => AddressState::Available,
                        'state_reason' => null,
                    ]);
            }

            if ($this->reservedAddressIds !== []) {
                Address::query()
                    ->whereIn('id', $this->reservedAddressIds)
                    ->where('state_reason', AddressStateReason::Migration)
                    ->update([
                        'server_id' => $server->id,
                        'state' => AddressState::Assigned,
                        'state_reason' => null,
                    ]);
            }

            $server->forceFill([
                'node_id' => $target->id,
                'network_interface_id' => $this->targetInterfaceId,
                // A released address may have been this server's primary; the
                // FK is nullOnDelete, not nullOnRelease, so clear it here and
                // let ServerNetworkService pick a primary from what is left.
                'primary_ipv4_address_id' => $this->stillHeld($server->primary_ipv4_address_id),
                'primary_ipv6_address_id' => $this->stillHeld($server->primary_ipv6_address_id),
            ])->save();

            // The operator's own request was audited when they made it; this
            // records that the guest actually landed, from a queue worker with
            // nobody logged in -- the same actor the placement reconciler uses.
            Audit::record(
                AuditEvent::ADMIN_SERVER_REHOMED,
                $server,
                [
                    'from' => $previous->name,
                    'to' => $target->name,
                    'vmid' => $server->vmid,
                    'disposition' => $this->disposition->value,
                ],
                SystemActor::instance(),
            );
        });

        // Refresh so the network sync reads the destination node and bridge
        // rather than the ones this job started with.
        $server->refresh();

        if ($this->disposition === MigrationDisposition::Reallocate) {
            $network->syncSettings($server);
        }

        $this->step->markCompleted();
    }

    /** Null out a primary address the server no longer holds. */
    private function stillHeld(?int $addressId): ?int
    {
        if ($addressId === null || ! in_array($addressId, $this->releasedAddressIds, true)) {
            return $addressId;
        }

        return null;
    }

    /**
     * A failed rebind leaves the guest on the destination and the row on the
     * source, which the placement reconciler resolves within a poll. The
     * reservation must not outlive the attempt, though, or those addresses are
     * held out of the pool by a migration that is over.
     */
    public function failed(?\Throwable $exception): void
    {
        Address::query()
            ->whereIn('id', $this->reservedAddressIds)
            ->where('state_reason', AddressStateReason::Migration)
            ->update(['state' => AddressState::Available, 'state_reason' => null, 'server_id' => null]);

        $this->step->markFailed($exception);
    }
}
