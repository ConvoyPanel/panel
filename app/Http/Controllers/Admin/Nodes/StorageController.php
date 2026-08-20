<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\Storage\StorageData;
use App\Data\Storage\StorageEloquentData;
use App\Enums\Audit\AuditEvent;
use App\Exceptions\Proxmox\RequestException;
use App\Facades\Audit;
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
        // What the storage may hold comes from the node, not the request. The
        // form used to submit six tick boxes for it; PVE already publishes the
        // answer, and the poll keeps it current from here on.
        $reported = $this->liveStorage->get($node, $request->string('name')->toString());

        $storage = $this->connection->transaction(function () use ($request, $node, $reported) {
            $storage = Storage::create([
                ...$request->validated(),
                'stores_kvm' => (bool) $reported?->storesKvm,
                'stores_lxc' => (bool) $reported?->storesLxc,
                'stores_lxc_templates' => (bool) $reported?->storesLxcTemplates,
                'stores_backups' => (bool) $reported?->storesBackups,
                'stores_iso' => (bool) $reported?->storesIso,
                'stores_snippets' => (bool) $reported?->storesSnippets,
            ]);

            StorageToNode::create([
                'storage_id' => $storage->id,
                'node_id' => $node->id,
            ]);

            return $storage;
        });

        Audit::record(
            AuditEvent::ADMIN_NODE_STORAGE_CREATED,
            subject: $node,
            properties: ['name' => $storage->name, 'stores_backups' => $storage->stores_backups],
        );

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

            // Nothing here assigns a backup order any more. It used to be
            // granted when the operator first ticked "stores backups", but that
            // box is gone -- content comes from Proxmox now -- and the pivot
            // sorts on creation, so a backup-capable storage already has one by
            // the time it can be edited.

            Audit::record(
                AuditEvent::ADMIN_NODE_STORAGE_UPDATED,
                subject: $node,
                properties: ['name' => $storage->name, 'changed' => array_keys($storage->getChanges())],
            );
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

            Audit::record(
                AuditEvent::ADMIN_NODE_STORAGE_BACKUP_ORDER_UPDATED,
                subject: $node,
                properties: ['order' => $request->array('ids')],
            );
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
        $detachedOnly = $storage->nodes()->count() > 1;

        if ($detachedOnly) {
            $storage->nodes()->detach($node->id);
        } else {
            $storage->delete();
        }

        // Two materially different outcomes behind one endpoint: detaching leaves the pool intact
        // for other nodes, deleting does not. The log has to say which happened.
        Audit::record(
            AuditEvent::ADMIN_NODE_STORAGE_DELETED,
            subject: $node,
            properties: ['name' => $storage->name, 'detached_only' => $detachedOnly],
        );

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
        // Eager-loaded so naming the other nodes costs one query, not one per row.
        $storages->loadMissing('nodes');

        return StorageEloquentData::collect(
            $storages->map(fn (Storage $storage) => StorageEloquentData::fromModel(
                $storage,
                $live->get($storage->name),
                $node,
            ))->all(),
            DataCollection::class,
        );
    }
}
