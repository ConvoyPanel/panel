<?php

namespace App\Services\Servers;

use App\Data\Server\Migration\MigrationAddressData;
use App\Data\Server\Migration\MigrationCandidateData;
use App\Data\Server\Migration\MigrationPlanData;
use App\Data\Server\Migration\MigrationPreviewData;
use App\Data\Server\Proxmox\Migration\MigrationPreconditionData;
use App\Enums\Network\AddressVersion;
use App\Enums\Server\MigrationDisposition;
use App\Enums\Server\MigrationTransport;
use App\Exceptions\Service\Address\InsufficientAddressesException;
use App\Models\Address;
use App\Models\AddressBlockGroup;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Services\Addresses\AddressAllocationService;
use App\Services\Addresses\AddressReachabilityService;
use App\Services\Proxmox\Server\ProxmoxMigrationClient;
use App\Support\Anchor\AnchorMigrationProtocol;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
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
 * Two transports, chosen by where the destination sits rather than by the
 * operator. A member of the source's own cluster is `qm migrate`, which can
 * move a running guest. Anything else is the Anchor transport: `vzdump` on the
 * source, a direct node-to-node download, `qmrestore` on the destination, and
 * the guest down for all of it. `qm remote-migrate` is not a third option;
 * docs/migration-anchor-contract.md records why.
 *
 * QEMU only. LXC has its own endpoint and none of this is wired to it.
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
     * Every node this server could be sent to, with a verdict each.
     *
     * Two groups, one list. The operator picks a destination; which transport
     * carries it there is the panel's answer, shown on the row rather than
     * offered as a choice. A node outside the cluster is listed even when it
     * cannot take the guest, because "pve7 has no Anchor" is something the
     * operator can act on and a missing row is not.
     */
    public function plan(Server $server): MigrationPlanData
    {
        $source = $server->node;

        $clusterTargets = $this->clusterTargets($source);
        $anchorTargets = $this->anchorTargets($source);

        if ($clusterTargets->isEmpty() && $anchorTargets->isEmpty()) {
            return new MigrationPlanData(
                sourceNodeId: $source->id,
                sourceNodeName: $source->name,
                isRunning: false,
                localResources: [],
                candidates: [],
                emptyReason: 'No other node is registered in Convoy.',
            );
        }

        $preconditions = $this->client->setServer($server)->getPreconditions();

        $candidates = $clusterTargets
            ->map(fn (Node $target) => $this->verdict($server, $target, $preconditions, MigrationTransport::Cluster))
            ->concat($anchorTargets->map(
                fn (Node $target) => $this->verdict($server, $target, $preconditions, MigrationTransport::Anchor),
            ));

        return new MigrationPlanData(
            sourceNodeId: $source->id,
            sourceNodeName: $source->name,
            isRunning: $preconditions->isRunning,
            localResources: $preconditions->localResources,
            candidates: $candidates->all(),
            emptyReason: null,
        );
    }

    /**
     * Which transport reaches this node. The whole of the decision.
     */
    public function transportFor(Node $source, Node $target): MigrationTransport
    {
        $cluster = $source->cluster;

        return $cluster !== null
            && ! $cluster->isStandalone()
            && $target->cluster_id === $cluster->id
                ? MigrationTransport::Cluster
                : MigrationTransport::Anchor;
    }

    /**
     * @return Collection<int, Node>
     */
    private function clusterTargets(Node $source): Collection
    {
        $cluster = $source->cluster;

        if ($cluster === null || $cluster->isStandalone()) {
            return new Collection;
        }

        return Node::query()
            ->where('cluster_id', $cluster->id)
            ->whereKeyNot($source->id)
            ->with('location')
            ->orderBy('name')
            ->get();
    }

    /**
     * Every other node, whether or not it can actually take the guest.
     *
     * @return Collection<int, Node>
     */
    private function anchorTargets(Node $source): Collection
    {
        $cluster = $source->cluster;
        $clustered = $cluster !== null && ! $cluster->isStandalone();

        return Node::query()
            ->whereKeyNot($source->id)
            ->when($clustered, fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->whereNull('cluster_id')
                    ->orWhere('cluster_id', '!=', $cluster->id),
            ))
            ->with('location')
            ->orderBy('name')
            ->get();
    }

    /**
     * The verdict for one destination, resolved down to the actual addresses.
     */
    public function preview(Server $server, Node $target): MigrationPreviewData
    {
        $candidate = $this->verdict(
            $server,
            $target,
            $this->client->setServer($server)->getPreconditions(),
            $this->transportFor($server->node, $target),
        );

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
        $candidate = $this->verdict(
            $server,
            $target,
            $preconditions,
            $this->transportFor($server->node, $target),
        );

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

    private function verdict(
        Server $server,
        Node $target,
        MigrationPreconditionData $preconditions,
        MigrationTransport $transport,
    ): MigrationCandidateData {
        $bridge = $this->sameNamedBridge($server, $target);
        $anchor = $transport === MigrationTransport::Anchor;

        $blocked = fn (string $reason) => new MigrationCandidateData(
            nodeId: $target->id,
            nodeName: $target->name,
            locationName: $target->location->short_code ?? $target->location->name ?? '',
            disposition: MigrationDisposition::Blocked,
            transport: $transport,
            estimatedTransferBytes: $anchor ? $server->disk : null,
            blockedReason: $reason,
            bridgeName: $bridge?->name,
            canMigrateOnline: false,
        );

        // PVE first: there is no point telling an operator their addresses
        // would follow to a node that will not take the guest at all. A passed
        // through device blocks both transports, for different reasons that
        // amount to the same thing: `qm migrate` refuses outright, and an
        // archive restored elsewhere would reference a device that is not
        // there.
        if ($preconditions->localResources !== []) {
            return $blocked(sprintf(
                'Proxmox will not migrate this guest anywhere while %s is passed through to it.',
                implode(', ', $preconditions->localResources),
            ));
        }

        if ($anchor) {
            $refusal = $this->anchorRefusal($server, $target);

            if ($refusal !== null) {
                return $blocked($refusal);
            }
        } else {
            // `allowed_nodes` and `not_allowed_nodes` are the source cluster's
            // opinion of its own members. A node outside it is absent from
            // both lists, so asking these questions of an Anchor destination
            // would block every one of them.
            $blocker = $preconditions->blockerFor($target->name);

            if ($blocker !== null) {
                return $blocked($blocker->summary() ?? 'Proxmox will not accept this node as a destination.');
            }

            if ($preconditions->allowedNodes !== [] && ! in_array($target->name, $preconditions->allowedNodes, true)) {
                return $blocked('Proxmox will not accept this node as a destination.');
            }
        }

        $held = $server->addresses()->with('addressBlock')->get();
        $sourceBridge = $server->networkInterface?->name;

        if ($sourceBridge === null) {
            // Nothing pins the server to a bridge, so there is nothing to
            // match and nothing to strand. Whatever the guest's NIC references
            // is outside Convoy's model, so leave it alone.
            return $held->isEmpty()
                ? $this->preserve($server, $target, null, $preconditions, $transport)
                : $blocked('This server holds addresses but is not attached to a network interface. Attach it to one before migrating.');
        }

        if ($bridge !== null) {
            $stranded = $this->reachability->unreachableGroupsFor($server, $bridge);

            if ($stranded->isEmpty()) {
                return $this->preserve($server, $target, $bridge, $preconditions, $transport);
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
            transport: $transport,
            estimatedTransferBytes: $anchor ? $server->disk : null,
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
        MigrationTransport $transport,
    ): MigrationCandidateData {
        return new MigrationCandidateData(
            nodeId: $target->id,
            nodeName: $target->name,
            locationName: $target->location->short_code ?? $target->location->name ?? '',
            disposition: MigrationDisposition::Preserve,
            transport: $transport,
            estimatedTransferBytes: $transport === MigrationTransport::Anchor ? $server->disk : null,
            blockedReason: null,
            bridgeName: $bridge?->name,
            // The Anchor transport dumps, transfers and restores; there is no
            // supported way to do that without stopping the guest, so it never
            // matters that the guest happens to be running now.
            canMigrateOnline: $transport === MigrationTransport::Cluster && $preconditions->isRunning,
        );
    }

    /**
     * Why this node cannot take the guest over Anchor, or null if it can.
     *
     * Ordered so the operator is told the thing they can fix. Enrollment
     * first, because it is the one with a single remedy and the one that makes
     * every later question unanswerable. The capability checks are separate
     * from enrollment on purpose: an enrolled Anchor that has not been built
     * for migration would accept the install and finish it by running
     * `qm template` on the guest, which is not a failure the panel can detect
     * afterwards.
     */
    public function anchorRefusal(Server $server, Node $target): ?string
    {
        $source = $server->node;

        foreach ([$source, $target] as $node) {
            if (! $node->hasAnchor()) {
                return sprintf('Anchor is not installed on %s. Both nodes need it to migrate between clusters.', $node->name);
            }
        }

        if (! $this->advertises($source, AnchorMigrationProtocol::EXPORT_CAPABILITY)) {
            return sprintf('Anchor on %s is too old to export a guest for migration. Upgrade it first.', $source->name);
        }

        if (! $this->advertises($target, AnchorMigrationProtocol::INSTALL_CAPABILITY)) {
            return sprintf('Anchor on %s is too old to install a migrated guest. Upgrade it first.', $target->name);
        }

        if ($this->destinationStorage($server, $target) === null) {
            return sprintf(
                '%s has no storage named "%s" for the guest\'s disks to be restored onto.',
                $target->name,
                $server->storage->name,
            );
        }

        return null;
    }

    /**
     * The storage on $target the guest's disks are restored onto.
     *
     * Matched by name, which is the same rule the bridge check already uses
     * and for the same reason: `storage.cfg` names are how a Proxmox operator
     * says "these two are the same thing", and the panel is in no position to
     * decide that `local-lvm` and `fast-nvme` are interchangeable.
     */
    public function destinationStorage(Server $server, Node $target): ?Storage
    {
        return $target->storages()
            ->where('storages.name', $server->storage->name)
            ->first();
    }

    private function advertises(Node $node, string $capability): bool
    {
        return in_array($capability, $node->agent_capabilities ?? [], true);
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
