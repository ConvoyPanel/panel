<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\Storage\StorageData;
use App\Data\Storage\StorageEloquentData;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nodes\Storages\StorageRequest;
use App\Http\Requests\Admin\Nodes\Storages\UpdateBackupOrderRequest;
use App\Models\Node;
use App\Models\Storage;
use App\Models\StorageToNode;
use App\Repositories\Proxmox\Node\ProxmoxStorageRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\DataCollection;
use Throwable;

class StorageController extends Controller
{
    public function __construct(
        private ProxmoxStorageRepository $repository,
        private ConnectionInterface $connection,
    ) {}

    public function index(Node $node)
    {
        return $this->mapWithLiveData(
            $node,
            $node->storages()->withUsageSums()->orderBy('id', 'desc')->get(),
        );
    }

    /**
     * @throws RequestException
     */
    public function fetchFromProxmox(Node $node)
    {
        return StorageData::collect(
            $this->repository->setNode($node)->getStorages(),
            DataCollection::class,
        );
    }

    /**
     * @throws Throwable
     */
    public function store(StorageRequest $request, Node $node)
    {
        $storage = $this->connection->transaction(function () use ($request, $node) {
            $storage = Storage::create($request->validated());

            StorageToNode::create([
                'storage_id' => $storage->id,
                'node_id' => $node->id,
            ]);

            return $storage;
        });

        return StorageEloquentData::fromModel(
            $storage,
            $this->liveStorages($node)->get($storage->name),
        );
    }

    /**
     * @throws Throwable
     */
    public function update(StorageRequest $request, Node $node, Storage $storage)
    {
        $this->connection->transaction(function () use ($request, $storage) {
            $storage->update($request->validated());

            if ($request->boolean('stores_backups') && $storage->stores_backups !== $request->boolean('stores_backups')) {
                $storageToNode = StorageToNode::where('storage_id', '=', $storage->id)->firstOrFail();
                $storageToNode->backup_order = $storageToNode->getHighestOrderNumber() + 1;
                $storageToNode->save();
            }
        });

        return StorageEloquentData::fromModel(
            $storage,
            $this->liveStorages($node)->get($storage->name),
        );
    }

    public function updateBackupOrder(UpdateBackupOrderRequest $request, Node $node)
    {
        StorageToNode::setNewOrder(
            ids: $request->array('ids'),
            primaryKeyColumn: 'storage_id'
        );

        return $this->mapWithLiveData(
            $node,
            $node->storages()->withUsageSums()->orderBy('id', 'desc')->get(),
        );
    }

    public function destroy(Node $node, Storage $storage)
    {
        return response()->noContent();
    }

    /**
     * Merge the live Proxmox status (capacity/usage — the source of truth) into
     * each Convoy storage record. Degrades gracefully: a storage with no live
     * match (node offline / storage missing) comes back flagged `online: false`
     * with null physical figures rather than failing the whole list.
     *
     * @param  Collection<int, Storage>  $storages
     * @return DataCollection<int, StorageEloquentData>
     */
    private function mapWithLiveData(Node $node, Collection $storages): DataCollection
    {
        $live = $this->liveStorages($node);

        return StorageEloquentData::collect(
            $storages->map(fn (Storage $storage) => StorageEloquentData::fromModel(
                $storage,
                $live->get($storage->name),
            ))->all(),
            DataCollection::class,
        );
    }

    /**
     * Live Proxmox storage status keyed by storage name, cached briefly (the
     * figures move slowly and this is a per-request network call). Returns an
     * empty collection when the node is unreachable so callers can fall back.
     *
     * @return Collection<string, StorageData>
     */
    private function liveStorages(Node $node): Collection
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
}
