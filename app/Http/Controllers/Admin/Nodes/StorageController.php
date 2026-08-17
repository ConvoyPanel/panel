<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\Storage\StorageData;
use App\Data\Storage\StorageEloquentData;
use App\Exceptions\Proxmox\RequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nodes\Storages\StorageRequest;
use App\Http\Requests\Admin\Nodes\Storages\UpdateBackupOrderRequest;
use App\Models\Node;
use App\Models\Storage;
use App\Models\StorageToNode;
use App\Services\Nodes\LiveStorageService;
use App\Services\Proxmox\Node\ProxmoxStorageClient;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;
use Throwable;

class StorageController extends Controller
{
    public function __construct(
        private ProxmoxStorageClient $client,
        private LiveStorageService $liveStorage,
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
            $this->client->setNode($node)->getStorages(),
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
            $this->liveStorage->get($node, $storage->name),
        );
    }

    /**
     * @throws Throwable
     */
    public function update(StorageRequest $request, Node $node, Storage $storage)
    {
        $this->connection->transaction(function () use ($request, $node, $storage) {
            $storage->update($request->validated());

            if ($request->boolean('stores_backups') && $storage->stores_backups !== $request->boolean('stores_backups')) {
                // Scoped to this node as well as this storage. `storage_to_node`
                // is a composite pivot whose declared primary key is
                // `storage_id`, so a storage reachable from two nodes has two
                // rows that both answer to it -- and looking one up by storage
                // alone silently picks whichever the database returns first.
                $storageToNode = StorageToNode::query()
                    ->where('storage_id', $storage->id)
                    ->where('node_id', $node->id)
                    ->firstOrFail();

                $storageToNode->backup_order = $storageToNode->getHighestOrderNumber() + 1;
                $storageToNode->save();
            }
        });

        return StorageEloquentData::fromModel(
            $storage,
            $this->liveStorage->get($node, $storage->name),
        );
    }

    public function updateBackupOrder(UpdateBackupOrderRequest $request, Node $node)
    {
        /*
         * Written per node rather than through `setNewOrder()`, which matches on
         * `storage_id` alone: a storage mounted by several nodes would have its
         * order rewritten on all of them by a drag performed on one. Backup
         * order is a property of "this node's preference", not of the storage.
         */
        $this->connection->transaction(function () use ($request, $node) {
            foreach ($request->array('ids') as $position => $storageId) {
                StorageToNode::query()
                    ->where('storage_id', $storageId)
                    ->where('node_id', $node->id)
                    ->update(['backup_order' => $position + 1]);
            }
        });

        return $this->mapWithLiveData(
            $node,
            $node->storages()->withUsageSums()->orderBy('id', 'desc')->get(),
        );
    }

    public function destroy(Node $node, Storage $storage)
    {
        abort_unless(
            $node->storages()->whereKey($storage->getKey())->exists(),
            404,
        );

        /*
         * Detach rather than delete when other nodes still reach this storage.
         * Removing it from one node's list is not a statement about the pool
         * itself, and deleting the row would silently take it off every other
         * node that was using it.
         */
        if ($storage->nodes()->count() > 1) {
            $storage->nodes()->detach($node->id);
        } else {
            $storage->delete();
        }

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
        $live = $this->liveStorage->forNode($node);

        return StorageEloquentData::collect(
            $storages->map(fn (Storage $storage) => StorageEloquentData::fromModel(
                $storage,
                $live->get($storage->name),
            ))->all(),
            DataCollection::class,
        );
    }
}
