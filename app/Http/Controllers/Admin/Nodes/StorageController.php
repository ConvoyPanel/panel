<?php

namespace App\Http\Controllers\Admin\Nodes;

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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;

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
     * @throws ConnectionException
     */
    public function fetchFromProxmox(Node $node): JsonResponse
    {
        $storages = $this->repository->setNode($node)->getStorages();

        return fractal($storages, new StorageDataTransformer)->respond();
    }

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

    public function update(StorageRequest $request, Node $node, Storage $storage): JsonResponse
    {
        $storage->update($request->validated());

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

    public function destroy(Node $node, Storage $storage): JsonResponse
    {
        // TODO: implement StorageController destroy method

        return response()->noContent();
    }
}
