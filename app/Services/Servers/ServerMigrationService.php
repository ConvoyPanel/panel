<?php

namespace App\Services\Servers;

use App\Data\Server\Migration\MigrationAddressData;
use App\Data\Server\Migration\MigrationCandidateData;
use App\Data\Server\Migration\MigrationPlanData;
use App\Data\Server\Migration\MigrationPreviewData;
use App\Data\Server\Proxmox\Migration\MigrationPreconditionData;
use App\Enums\Network\AddressVersion;
use App\Enums\Server\MigrationDisposition;
use App\Exceptions\Service\Address\InsufficientAddressesException;
use App\Models\Address;
use App\Models\AddressBlockGroup;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Services\Addresses\AddressAllocationService;
use App\Services\Addresses\AddressReachabilityService;
use App\Services\Proxmox\Server\ProxmoxMigrationClient;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;

/**
 * Decides where a server may move and what that does to its addresses.
 *
 * Two facts are combined and neither is guessed. PVE's own preflight
 * (`GET /nodes/{node}/qemu/{vmid}/migrate`) says which members will take the
 * guest: storage availability, passed-through devices and HA affinity are its
 * answer, not something the panel re-derives. Convoy's pool-to-bridge pivot
 * says whether the addresses can follow. Neither is sufficient alone.
 *
 * The address verdict turns on how the destination's bridges differ:
 *
 *  - A bridge of the same name, attached to every pool the server's addresses
 *    live in: the address follows. This is the stretched-VLAN / shared-fabric
 *    case, and the one PVE's own model assumes.
 *  - A bridge of the same name that is *not* attached to those pools: refused.
 *    PVE treats same-named bridges as one network and Convoy does not, and the
 *    likeliest explanation is an incomplete topology model rather than two
 *    genuinely different networks. Attaching the pool is one click; putting a
 *    guest on an unroutable address is an outage.
 *  - No bridge of that name, but some other bridge that can supply what the
 *    server holds: the networks really are different, so the addresses go back
 *    to their pool and the server gets new ones. The guest's IP changes.
 *
 * Intra-cluster only. Cross-cluster is `remote_migrate`, LXC has its own
 * endpoint, and neither is wired up.
 */
class ServerMigrationService
{
    public function __construct(
        private ProxmoxMigrationClient $client,
        private AddressReachabilityService $reachability,
        private AddressAllocationService $allocator,
        private ConnectionInterface $connection,
    ) {}

    /**
     * Every registered member of this server's cluster, with a verdict each.
     */
    public function plan(Server $server): MigrationPlanData
    {
        $source = $server->node;
        $cluster = $source->cluster;

        $empty = fn (string $reason) => new MigrationPlanData(
            sourceNodeId: $source->id,
            sourceNodeName: $source->name,
            isRunning: false,
            localResources: [],
            candidates: [],
            emptyReason: $reason,
        );

        if ($cluster === null || $cluster->isStandalone()) {
            return $empty('This node is not in a Proxmox cluster, so there is nowhere to migrate to.');
        }

        $targets = Node::query()
            ->where('cluster_id', $cluster->id)
            ->whereKeyNot($source->id)
            ->with('location')
            ->orderBy('name')
            ->get();

        if ($targets->isEmpty()) {
            return $empty('No other member of this cluster is registered in Convoy.');
        }

        $preconditions = $this->client->setServer($server)->getPreconditions();

        return new MigrationPlanData(
            sourceNodeId: $source->id,
            sourceNodeName: $source->name,
            isRunning: $preconditions->isRunning,
            localResources: $preconditions->localResources,
            candidates: $targets
                ->map(fn (Node $target) => $this->verdict($server, $target, $preconditions))
                ->all(),
            emptyReason: null,
        );
    }

    /**
     * The verdict for one destination, resolved down to the actual addresses.
     */
    public function preview(Server $server, Node $target): MigrationPreviewData
    {
        $candidate = $this->verdict($server, $target, $this->client->setServer($server)->getPreconditions());

        $held = $server->addresses()->with('addressBlock.addressBlockGroup')->get();

        if ($candidate->disposition !== MigrationDisposition::Reallocate) {
            return new MigrationPreviewData(
                candidate: $candidate,
                preserved: $candidate->disposition === MigrationDisposition::Preserve
                    ? $this->present($held)
                    : [],
                released: [],
                allocated: [],
                isShortOnAddresses: false,
            );
        }

        $interface = $this->reallocationInterface($server, $target, $held);
        $counts = $this->versionCounts($held);

        $allocated = $interface === null
            ? null
            : $this->previewAllocation($interface->id, $counts[0], $counts[1]);

        return new MigrationPreviewData(
            candidate: $candidate,
            preserved: [],
            released: $this->present($held),
            allocated: $allocated === null ? [] : $this->present($allocated),
            isShortOnAddresses: $allocated === null,
        );
    }

    /**
     * Re-run the verdict at commit time and hand back everything the caller
     * needs to act on it. One preflight call, because the answer has to be the
     * same one the rest of the decision is made from.
     *
     * @return array{MigrationCandidateData, ?NetworkInterface, bool} verdict, destination bridge, whether the guest is running
     */
    public function resolve(Server $server, Node $target): array
    {
        $preconditions = $this->client->setServer($server)->getPreconditions();
        $candidate = $this->verdict($server, $target, $preconditions);

        if ($candidate->disposition === MigrationDisposition::Blocked) {
            return [$candidate, null, $preconditions->isRunning];
        }

        $held = $server->addresses()->with('addressBlock')->get();

        $interface = $candidate->disposition === MigrationDisposition::Preserve
            ? $this->sameNamedBridge($server, $target)
            : $this->reallocationInterface($server, $target, $held);

        return [$candidate, $interface, $preconditions->isRunning];
    }

    /**
     * Free addresses on $interface, enough to replace what the server holds.
     * Runs the real allocator, so what it hands back is what a commit would.
     *
     * @return Collection<int, Address>
     *
     * @throws InsufficientAddressesException
     */
    public function allocateReplacements(Server $server, NetworkInterface $interface): Collection
    {
        [$ipv4, $ipv6] = $this->versionCounts($server->addresses()->with('addressBlock')->get());

        return $this->allocator->handle($interface->id, $ipv4, $ipv6);
    }

    private function verdict(Server $server, Node $target, MigrationPreconditionData $preconditions): MigrationCandidateData
    {
        $bridge = $this->sameNamedBridge($server, $target);

        $blocked = fn (string $reason) => new MigrationCandidateData(
            nodeId: $target->id,
            nodeName: $target->name,
            locationName: $target->location->short_code ?? $target->location->name ?? '',
            disposition: MigrationDisposition::Blocked,
            blockedReason: $reason,
            bridgeName: $bridge?->name,
            canMigrateOnline: false,
        );

        // PVE first: there is no point telling an operator their addresses
        // would follow to a node that will not take the guest at all.
        if ($preconditions->localResources !== []) {
            return $blocked(sprintf(
                'Proxmox will not migrate this guest anywhere while %s is passed through to it.',
                implode(', ', $preconditions->localResources),
            ));
        }

        $blocker = $preconditions->blockerFor($target->name);

        if ($blocker !== null) {
            return $blocked($blocker->summary() ?? 'Proxmox will not accept this node as a destination.');
        }

        if ($preconditions->allowedNodes !== [] && ! in_array($target->name, $preconditions->allowedNodes, true)) {
            return $blocked('Proxmox will not accept this node as a destination.');
        }

        $held = $server->addresses()->with('addressBlock')->get();
        $sourceBridge = $server->networkInterface?->name;

        if ($sourceBridge === null) {
            // Nothing pins the server to a bridge, so there is nothing to
            // match and nothing to strand. Whatever the guest's NIC references
            // is outside Convoy's model, so leave it alone.
            return $held->isEmpty()
                ? $this->preserve($server, $target, null, $preconditions)
                : $blocked('This server holds addresses but is not attached to a network interface. Attach it to one before migrating.');
        }

        if ($bridge !== null) {
            $stranded = $this->reachability->unreachableGroupsFor($server, $bridge);

            if ($stranded->isEmpty()) {
                return $this->preserve($server, $target, $bridge, $preconditions);
            }

            return $blocked(sprintf(
                '"%s" on %s is not attached to %s. Attach the pool to that bridge, or rename the bridge if it fronts a different network.',
                $sourceBridge,
                $target->name,
                $stranded->map(fn (AddressBlockGroup $group) => '"'.$group->name.'"')->join(', ', ' and '),
            ));
        }

        // No bridge of that name on the destination. PVE would migrate the
        // config offline and then fail to start the guest, so the NIC has to be
        // rewritten, which means the addresses cannot follow either.
        if ($held->isEmpty()) {
            return $blocked(sprintf(
                '%s has no bridge named "%s", and Convoy knows of no other bridge there to move this server onto.',
                $target->name,
                $sourceBridge,
            ));
        }

        $replacement = $this->reallocationInterface($server, $target, $held);

        if ($replacement === null) {
            return $blocked(sprintf(
                '%s has no bridge named "%s", and no bridge there has enough free addresses to replace this server\'s.',
                $target->name,
                $sourceBridge,
            ));
        }

        return new MigrationCandidateData(
            nodeId: $target->id,
            nodeName: $target->name,
            locationName: $target->location->short_code ?? $target->location->name ?? '',
            disposition: MigrationDisposition::Reallocate,
            blockedReason: null,
            bridgeName: $replacement->name,
            // A new address only reaches the guest when cloud-init re-runs, so
            // the guest is stopped for the move whatever its current state.
            canMigrateOnline: false,
        );
    }

    private function preserve(
        Server $server,
        Node $target,
        ?NetworkInterface $bridge,
        MigrationPreconditionData $preconditions,
    ): MigrationCandidateData {
        return new MigrationCandidateData(
            nodeId: $target->id,
            nodeName: $target->name,
            locationName: $target->location->short_code ?? $target->location->name ?? '',
            disposition: MigrationDisposition::Preserve,
            blockedReason: null,
            bridgeName: $bridge?->name,
            canMigrateOnline: $preconditions->isRunning,
        );
    }

    private function sameNamedBridge(Server $server, Node $target): ?NetworkInterface
    {
        $name = $server->networkInterface?->name;

        return $name === null ? null : NetworkInterface::query()
            ->where('node_id', $target->id)
            ->where('name', $name)
            ->first();
    }

    /**
     * The first bridge on $target that could supply replacements for everything
     * the server holds. Ordered by id so the answer is stable between the
     * preview and the commit.
     *
     * @param  Collection<int, Address>  $held
     */
    private function reallocationInterface(Server $server, Node $target, Collection $held): ?NetworkInterface
    {
        [$ipv4, $ipv6] = $this->versionCounts($held);

        return NetworkInterface::query()
            ->where('node_id', $target->id)
            ->orderBy('id')
            ->get()
            ->first(fn (NetworkInterface $interface) => $this->previewAllocation($interface->id, $ipv4, $ipv6) !== null);
    }

    /**
     * What the allocator would hand out, without handing it out.
     *
     * Runs the allocator for real inside a transaction and rolls it back, so
     * the preview is the allocator's own answer rather than a second
     * implementation of its rules. Minted rows are undone with it. Nothing is
     * reserved: a concurrent allocation can still take one of these, which is
     * why the commit path allocates again rather than trusting this.
     *
     * @return Collection<int, Address>|null null when the interface is short
     */
    private function previewAllocation(int $interfaceId, int $ipv4, int $ipv6): ?Collection
    {
        if ($ipv4 === 0 && $ipv6 === 0) {
            return new Collection;
        }

        $this->connection->beginTransaction();

        try {
            $allocated = $this->allocator->handle($interfaceId, $ipv4, $ipv6)
                ->each(fn (Address $address) => $address->loadMissing('addressBlock.addressBlockGroup'));

            // Touch every accessor the preview reads while the rows still
            // exist, so the rollback cannot pull a lazy load out from under it.
            return $allocated->values();
        } catch (InsufficientAddressesException) {
            return null;
        } finally {
            $this->connection->rollBack();
        }
    }

    /**
     * @param  Collection<int, Address>  $addresses
     * @return array{int, int} v4 count, v6 count
     */
    private function versionCounts(Collection $addresses): array
    {
        return [
            $addresses->filter(fn (Address $address) => $address->version === AddressVersion::IPv4)->count(),
            $addresses->filter(fn (Address $address) => $address->version === AddressVersion::IPv6)->count(),
        ];
    }

    /**
     * @param  Collection<int, Address>  $addresses
     * @return array<int, MigrationAddressData>
     */
    private function present(Collection $addresses): array
    {
        return $addresses->map(fn (Address $address) => MigrationAddressData::fromModel($address))->all();
    }
}
