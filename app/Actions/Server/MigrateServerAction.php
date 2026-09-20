<?php

namespace App\Actions\Server;

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\MigrationDisposition;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ProgressMode;
use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Service\Server\MigrationRefusedException;
use App\Jobs\Server\CommitServerMigrationJob;
use App\Jobs\Server\MigrateVmJob;
use App\Jobs\Server\SendPowerCommandJob;
use App\Jobs\Server\StopVmJob;
use App\Models\Address;
use App\Models\Deployment;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Services\Servers\ServerMigrationService;
use App\Traits\Actions\ManagesDeploymentLifecycle;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;

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
 */
class MigrateServerAction
{
    use ManagesDeploymentLifecycle;

    public function __construct(
        private ServerMigrationService $migration,
        private ConnectionInterface $connection,
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

        return $this->connection->transaction(function () use (
            $server,
            $target,
            $candidate,
            $interface,
            $wasRunning,
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

            Bus::chain(Arr::flatten([
                $this->onStart($deployment),
                $this->jobsFor($deployment, $target, $candidate->disposition, $interface, $reserved, $released, $wasRunning),
                $this->onComplete($deployment),
            ]))
                ->catch($this->onFail($deployment, ServerLifecycle::MIGRATION_FAILED))
                ->dispatch();

            return $deployment;
        });
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
}
