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
        return StorageEloquentData::collect(
            $node->storages()->withUsageSums()->orderBy('id', 'desc')->get(),
            DataCollection::class,
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

        return StorageEloquentData::from($storage);
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

        return StorageEloquentData::from($storage);
    }

    public function updateBackupOrder(UpdateBackupOrderRequest $request, Node $node)
    {
        StorageToNode::setNewOrder(
            ids: $request->array('ids'),
            primaryKeyColumn: 'storage_id'
        );

        return StorageEloquentData::collect(
            $node->storages()->withUsageSums()->orderBy('id', 'desc')->get(),
            DataCollection::class,
        );
    }

    public function destroy(Node $node, Storage $storage)
    {
        return response()->noContent();
    }
}
