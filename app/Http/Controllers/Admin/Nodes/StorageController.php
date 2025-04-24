<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nodes\Storages\StoreStorageRequest;
use App\Http\Requests\Admin\Nodes\Storages\UpdateBackupOrderRequest;
use App\Http\Requests\Admin\Nodes\Storages\UpdateStorageRequest;
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
        return fractal($node->storages()->orderBy('id', 'desc')->get(), new StorageTransformer)->respond();
    }

    /**
     * @throws ConnectionException
     */
    public function fetchFromProxmox(Node $node): JsonResponse
    {
        $storages = $this->repository->setNode($node)->getStorages();

        return fractal($storages, new StorageDataTransformer)->respond();
    }

    public function store(StoreStorageRequest $request, Node $node): JsonResponse
    {
        $storage = $this->connection->transaction(function () use ($request, $node) {
            $storage = Storage::create($request->validated());

            $node->storages()->attach($storage->id, [
                'backup_order' => $request->input('backup_order'),
            ]);

            return $storage;
        });

        return fractal($storage, new StorageTransformer)->respond();
    }

    public function update(UpdateStorageRequest $request, Node $_, Storage $storage): JsonResponse
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

        return fractal($node->storages()->orderBy('id', 'desc')->get(), new StorageTransformer)->respond();
    }
}
