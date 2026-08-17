<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\Storage\StorageData;
use App\Data\Storage\StorageEloquentData;
use App\Exceptions\Proxmox\RequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nodes\Storages\AttachStorageRequest;
use App\Http\Requests\Admin\Nodes\Storages\StorageRequest;
use App\Http\Requests\Admin\Nodes\Storages\UpdateBackupOrderRequest;
use App\Models\Node;
use App\Models\Storage;
use App\Models\StorageToNode;
use App\Services\Nodes\LiveStorageService;
use App\Services\Proxmox\Node\ProxmoxStorageClient;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
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
     * Storages this node could be given, and would accept.
     *
     * Filtered rather than validated-on-submit: offering a choice that will be
     * refused is a worse experience than not offering it, and every condition
     * `attach()` enforces is knowable up front. An empty list is the honest
     * answer for a standalone host, which can never share.
     *
     * @throws RequestException
     */
    public function attachable(Node $node)
    {
        if ($node->cluster_name === null) {
            return StorageEloquentData::collect([], DataCollection::class);
        }

        $reported = $this->liveStorage->forNode($node);

        $candidates = Storage::query()
            ->whereDoesntHave('nodes', fn ($query) => $query->whereKey($node->getKey()))
            ->whereHas('nodes', fn ($query) => $query->where('nodes.cluster_name', $node->cluster_name))
            ->withUsageSums()
            ->get()
            // Proxmox has to report it here, or attaching would assert a
            // reachability Convoy has never observed.
            ->filter(fn (Storage $storage) => $reported->has($storage->name))
            ->values();

        return StorageEloquentData::collect(
            $candidates->map(fn (Storage $storage) => StorageEloquentData::fromModel(
                $storage,
                $reported->get($storage->name),
            ))->all(),
            DataCollection::class,
        );
    }

    /**
     * Point an already-registered storage at a second node.
     *
     * Separate from `store()` because they are different intentions: `store()`
     * brings a PVE storage under Convoy's management, this says an existing pool
     * is reachable from somewhere else too. Conflating them is what made shared
     * storage impossible to express -- every registration created its own row,
     * so one NFS export across three nodes was three unrelated records.
     *
     * The storage arrives in the body rather than the path because route model
     * bindings here are scoped to the node (`scopeBindings()` in bootstrap/app.php)
     * -- and a storage that is not yet attached to this node is, by definition,
     * exactly the one that cannot be resolved that way.
     *
     * @throws Throwable
     */
    public function attach(AttachStorageRequest $request, Node $node)
    {
        $storage = Storage::findOrFail($request->integer('storage_id'));

        if ($node->storages()->whereKey($storage->getKey())->exists()) {
            throw ValidationException::withMessages([
                'storage_id' => 'That storage is already attached to this node.',
            ]);
        }

        /*
         * A storage id is only meaningful inside the cluster that defines it --
         * `storage.cfg` is cluster-wide, so `local-lvm` on one cluster has
         * nothing to do with `local-lvm` on another. Attaching across that line
         * would assert the two are one pool, which is exactly the confusion this
         * feature exists to remove. Standalone hosts (null cluster) can never
         * share, so they are excluded by the same comparison.
         */
        $sharesCluster = $storage->nodes()
            ->when(
                $node->cluster_name === null,
                fn ($query) => $query->whereRaw('1 = 0'),
                fn ($query) => $query->where('nodes.cluster_name', $node->cluster_name),
            )
            ->exists();

        if (! $sharesCluster) {
            throw ValidationException::withMessages([
                'storage_id' => 'That storage belongs to a different Proxmox cluster, so it cannot be shared with this node.',
            ]);
        }

        /*
         * And Proxmox has to actually report it here. Taking the operator's word
         * for it is the same mistake as trusting a hand-set `shared` flag: the
         * panel would claim a reachability it has never observed, and the first
         * evidence would be a failed deployment.
         */
        if ($this->liveStorage->get($node, $storage->name) === null) {
            throw ValidationException::withMessages([
                'storage_id' => "Proxmox did not report a storage named \"{$storage->name}\" on this node.",
            ]);
        }

        StorageToNode::create([
            'storage_id' => $storage->id,
            'node_id' => $node->id,
        ]);

        return StorageEloquentData::fromModel(
            $storage->refresh(),
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
