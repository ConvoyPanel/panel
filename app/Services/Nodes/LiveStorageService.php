<?php

namespace App\Services\Nodes;

use App\Data\Node\Storage\StorageData;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Node;
use App\Repositories\Proxmox\Node\ProxmoxStorageRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Live Proxmox storage status is the source of truth for capacity/usage (it
 * includes the base system and any non-Convoy consumers). This centralizes the
 * per-node lookup, its short cache, and the offline-fallback contract so every
 * caller (the storage listing and the allocation-time capacity check) agrees.
 */
class LiveStorageService
{
    public function __construct(private ProxmoxStorageRepository $repository) {}

    /**
     * Live status keyed by storage name, cached briefly (the figures move
     * slowly and this is a per-request network call). Returns an empty
     * collection when the node is unreachable so callers can fall back.
     *
     * @return Collection<string, StorageData>
     */
    public function forNode(Node $node): Collection
    {
        return Cache::remember("node:{$node->id}:live-storages", now()->addSeconds(15), function () use ($node) {
            try {
                return collect($this->repository->setNode($node)->getStorages()->all())
                    ->keyBy(fn (StorageData $storage) => $storage->name);
            } catch (RequestException|ConnectionException) {
                return collect();
            }
        });
    }

    public function get(Node $node, string $name): ?StorageData
    {
        return $this->forNode($node)->get($name);
    }
}
