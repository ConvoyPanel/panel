<?php

namespace App\Services\Addresses;

use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Whether an address can actually be used from a given node or bridge.
 *
 * An address is bound to its block, its block to a pool, and the pool to a set
 * of bridges through `address_block_group_to_network_interface`. That pivot is
 * the only statement of reachability in the schema: a node can route an
 * address iff it owns an interface attached to that address's pool. Nothing on
 * the address row records a node, deliberately -- see
 * docs/ipam-migration-research.md §5.
 *
 * The rule used to live inside UpdateAddressRequest as a validation closure,
 * which meant it only ever ran when an operator typed something. Placement
 * changes it does not observe -- an HA recovery, an out-of-band `qm migrate`,
 * a panel-driven migration -- could leave a server holding addresses its new
 * node cannot reach, and the next network sync would write one of them into
 * `ipconfig0`. It is a standing invariant, so it lives where every caller can
 * re-check it.
 */
class AddressReachabilityService
{
    /**
     * Whether $node owns an interface attached to this address's pool.
     */
    public function isReachable(Address $address, Node $node): bool
    {
        return $this->groupIdsReachableFrom($node)
            ->contains($address->addressBlock->address_block_group_id);
    }

    /**
     * Whether the pool this address lives in is attached to a specific bridge.
     *
     * The stricter half of {@see isReachable()}: a node may own several
     * bridges and reach a pool through only one of them, so anything that
     * pins a server to a particular interface has to ask about that interface.
     */
    public function isReachableVia(Address $address, int $networkInterfaceId): bool
    {
        return NetworkInterface::query()
            ->where('id', $networkInterfaceId)
            ->whereHas(
                'addressBlockGroups',
                fn (Builder $groups) => $groups->where(
                    'address_block_groups.id',
                    $address->addressBlock->address_block_group_id,
                ),
            )
            ->exists();
    }

    /**
     * The blocks a node can route, through every pool attached to one of its
     * interfaces. Used to scope a containment test when reconciling addresses
     * observed on a guest: an unscoped test would happily match a block the
     * guest's node has no path to.
     *
     * @return Collection<int, AddressBlock>
     */
    public function blocksReachableFrom(Node $node): Collection
    {
        return AddressBlock::query()
            ->whereIn('address_block_group_id', $this->groupIdsReachableFrom($node)->all())
            ->get();
    }

    /**
     * Pools holding one of $server's addresses that $interface is not attached
     * to. Empty means the server keeps every address it holds when it lands on
     * that bridge.
     *
     * Scoped to the interface rather than the node because that is the bridge
     * the guest's NIC will actually sit on. A pool attached to some *other*
     * bridge on the same node is not a path for this server's traffic.
     *
     * @return Collection<int, AddressBlockGroup>
     */
    public function unreachableGroupsFor(Server $server, ?NetworkInterface $interface): Collection
    {
        $held = $this->groupIdsHeldBy($server);

        if ($held->isEmpty()) {
            return new Collection;
        }

        $attached = $interface === null
            ? new Collection
            : $interface->addressBlockGroups()->pluck('address_block_groups.id');

        $missing = $held->diff($attached);

        if ($missing->isEmpty()) {
            return new Collection;
        }

        return AddressBlockGroup::query()->whereIn('id', $missing->all())->get();
    }

    /**
     * Interfaces on $node that could carry every address $server currently
     * holds. An addressless server is carried by any of them.
     *
     * @return Collection<int, NetworkInterface>
     */
    public function interfacesCarrying(Server $server, Node $node): Collection
    {
        $held = $this->groupIdsHeldBy($server);

        $query = NetworkInterface::query()->where('node_id', $node->id);

        foreach ($held as $groupId) {
            $query->whereHas(
                'addressBlockGroups',
                fn (Builder $groups) => $groups->where('address_block_groups.id', $groupId),
            );
        }

        return $query->get();
    }

    /**
     * Pool ids holding at least one of this server's addresses.
     *
     * @return Collection<int, int>
     */
    public function groupIdsHeldBy(Server $server): Collection
    {
        return AddressBlock::query()
            ->whereIn('id', $server->addresses()->select('address_block_id'))
            ->distinct()
            ->pluck('address_block_group_id');
    }

    /**
     * @return Collection<int, int>
     */
    private function groupIdsReachableFrom(Node $node): Collection
    {
        return AddressBlockGroup::query()
            ->whereHas(
                'networkInterfaces',
                fn (Builder $interfaces) => $interfaces->where('network_interfaces.node_id', $node->id),
            )
            ->pluck('id');
    }
}
