<?php

namespace App\Services\Servers;

use App\Enums\Node\NodeStatus;
use App\Enums\Node\Storage\StorageContentType;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Storage;
use App\Services\Addresses\AddressAvailabilityService;
use App\Services\Nodes\LiveStorageService;
use Illuminate\Support\Collection;

/**
 * Picks the node, storage and bridge for a new server when the caller did not.
 *
 * An integration selling "a 2 GB server in Frankfurt" knows the location and the size, not
 * which of the location's hosts has room. Given a location it tries every node there, emptiest
 * first by memory, and takes the first that has a KVM storage with space for the disk and a
 * bridge with enough free addresses. Given a node it only fills in whichever of the storage and
 * bridge was left out.
 *
 * The result goes back through the create request's validation like any hand-picked placement,
 * so the capacity rules stay the last word. This only has to make a sensible first choice.
 */
class ServerPlacementResolver
{
    public function __construct(
        private LiveStorageService $liveStorage,
        private AddressAvailabilityService $addresses,
    ) {}

    /**
     * @return array{node_id: int, storage_id: int, network_interface_id: int}|null
     */
    public function resolve(
        ?int $locationId,
        ?int $nodeId,
        ?int $storageId,
        ?int $networkInterfaceId,
        int $cpu,
        int $memory,
        int $disk,
        int $ipv4Count,
        int $ipv6Count,
    ): ?array {
        $candidates = $nodeId !== null
            ? Node::query()->whereKey($nodeId)->get()
            : $this->nodesWithRoom($locationId, $cpu, $memory);

        foreach ($candidates as $node) {
            $storage = $this->storageFor($node, $storageId, $disk);
            $interface = $this->interfaceFor($node, $networkInterfaceId, $ipv4Count, $ipv6Count);

            if ($storage !== null && $interface !== null) {
                return [
                    'node_id' => $node->id,
                    'storage_id' => $storage,
                    'network_interface_id' => $interface,
                ];
            }
        }

        return null;
    }

    /**
     * Nodes in the location that can take the server's CPU and memory, emptiest first.
     *
     * Memory is counted against what is already allocated, which is stricter than the
     * hand-picked path's per-request check: a scheduler that only asked "is this server smaller
     * than the node" would keep filling the first node forever.
     *
     * @return Collection<int, Node>
     */
    private function nodesWithRoom(?int $locationId, int $cpu, int $memory): Collection
    {
        if ($locationId === null) {
            return collect();
        }

        return Node::query()
            ->where('location_id', $locationId)
            ->withSum('servers as allocated_memory', 'memory')
            ->get()
            ->reject(fn (Node $node) => $node->currentStatus() === NodeStatus::UNREACHABLE)
            ->map(function (Node $node) {
                $ceiling = $node->memory * (1 + $node->memory_overallocate / 100);

                return [
                    'node' => $node,
                    'ceiling' => $ceiling,
                    'free' => $ceiling - (int) $node->getAttribute('allocated_memory'),
                ];
            })
            ->filter(fn (array $candidate) => $cpu <= $candidate['node']->cpu_count && $memory <= $candidate['free'])
            ->sortByDesc(fn (array $candidate) => $candidate['ceiling'] > 0 ? $candidate['free'] / $candidate['ceiling'] : 0)
            ->pluck('node')
            ->values();
    }

    /**
     * A KVM storage on the node with room for the disk, the one with the most room first.
     *
     * A storage the node did not report on (offline, or not yet polled) is kept as a last
     * resort rather than dropped, the same way the capacity rule fails open for it.
     */
    private function storageFor(Node $node, ?int $storageId, int $disk): ?int
    {
        if ($storageId !== null) {
            return $node->storages()->whereKey($storageId)->exists() ? $storageId : null;
        }

        $storages = $node->storages()->stores(StorageContentType::KVM)->orderBy('storages.id')->get()
            ->map(fn (Storage $storage) => [
                'id' => $storage->id,
                'free' => $this->liveStorage->freeForConvoy($node, $storage),
            ])
            ->filter(fn (array $storage) => $storage['free'] === null || $storage['free'] >= $disk)
            ->sortByDesc(fn (array $storage) => $storage['free'] ?? -1);

        return $storages->first()['id'] ?? null;
    }

    /** The node's first bridge with enough free addresses for the server. */
    private function interfaceFor(Node $node, ?int $networkInterfaceId, int $ipv4Count, int $ipv6Count): ?int
    {
        $interfaces = $node->networkInterfaces()
            ->when($networkInterfaceId !== null, fn ($query) => $query->whereKey($networkInterfaceId))
            ->orderBy('id')
            ->get();

        foreach ($interfaces as $interface) {
            /** @var NetworkInterface $interface */
            if (($ipv4Count === 0 && $ipv6Count === 0)
                || $this->addresses->hasSufficientAddresses($interface->id, $ipv4Count, $ipv6Count)) {
                return $interface->id;
            }
        }

        return null;
    }
}
