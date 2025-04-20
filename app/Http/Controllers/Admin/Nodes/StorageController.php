<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Models\Storage;
use App\Http\Controllers\Controller;
use App\Models\Node;
use Illuminate\Database\ConnectionInterface;
use App\Repositories\Proxmox\Node\ProxmoxStorageRepository;
use App\Transformers\Admin\StorageDataTransformer;
use App\Transformers\Admin\StorageTransformer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;

use App\Http\Requests\Admin\Nodes\Storages\StoreStorageRequest;
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
}
