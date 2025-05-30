<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Exceptions\Repository\Proxmox\RequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nodes\Storages\StorageRequest;
use App\Http\Requests\Admin\Nodes\Storages\UpdateBackupOrderRequest;
use App\Models\Node;
use App\Models\Storage;
use App\Models\StorageToNode;
use App\Repositories\Proxmox\Node\ProxmoxStorageRepository;
use App\Transformers\Admin\StorageDataTransformer;
use App\Transformers\Admin\StorageTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Response;
use Throwable;

use function fractal;

class StorageController extends Controller
{
    public function __construct(
        private ProxmoxStorageRepository $repository,
        private ConnectionInterface $connection,
    ) {}

    public function index(Node $node): JsonResponse
    {
        return fractal($node->storages()->withUsageSums()->orderBy('id', 'desc')->get(), new StorageTransformer)->respond();
    }

    /**
     * @throws RequestException
     */
    public function fetchFromProxmox(Node $node): JsonResponse
    {
        $storages = $this->repository->setNode($node)->getStorages();

        return fractal($storages, new StorageDataTransformer)->respond();
    }

    /**
     * @throws Throwable
     */
    public function store(StorageRequest $request, Node $node): JsonResponse
    {
        $storage = $this->connection->transaction(function () use ($request, $node) {
            $storage = Storage::create($request->validated());

            StorageToNode::create([
                'storage_id' => $storage->id,
                'node_id' => $node->id,
            ]);

            return $storage;
        });

        return fractal($storage, new StorageTransformer)->respond();
    }

    /**
     * @throws Throwable
     */
    public function update(StorageRequest $request, Node $node, Storage $storage): JsonResponse
    {
        $this->connection->transaction(function () use ($request, $storage) {
            $storage->update($request->validated());

            // if the storage now supports backups, we need to update the order
            if ($request->boolean('stores_backups') && $storage->stores_backups !== $request->boolean('stores_backups')) {
                $storageToNode = StorageToNode::where('storage_id', '=', $storage->id)->firstOrFail();
                $storageToNode->backup_order = $storageToNode->getHighestOrderNumber() + 1;
                $storageToNode->save();
            }
        });

        return fractal($storage, new StorageTransformer)->respond();
    }

    public function updateBackupOrder(UpdateBackupOrderRequest $request, Node $node): JsonResponse
    {
        StorageToNode::setNewOrder(
            ids: $request->array('ids'),
            primaryKeyColumn: 'storage_id'
        );

        return fractal($node->storages()->withUsageSums()->orderBy('id', 'desc')->get(), new StorageTransformer)->respond();
    }

    public function destroy(Node $node, Storage $storage): Response
    {
        // TODO: implement StorageController destroy method

        return response()->noContent();
    }
}
