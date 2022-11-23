<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Enums\Server\Status;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\Backups\RollbackBackupRequest;
use Convoy\Http\Requests\Client\Servers\Backups\StoreBackupRequest;
use Convoy\Jobs\Server\MonitorBackupRestoreJob;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Repositories\Eloquent\BackupRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Convoy\Services\Servers\Backups\BackupCreationService;
use Convoy\Services\Servers\Backups\BackupDeletionService;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Transformers\Client\BackupTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BackupController extends ApplicationApiController
{
    public function __construct(private ConnectionInterface $connection, private ServerDetailService $detailService, private BackupCreationService $creationService, private BackupDeletionService $deletionService, private BackupRepository $backupRepository, private ProxmoxBackupRepository $proxmoxRepository)
    {
    }

    public function index(Server $server, Request $request)
    {
        $backups = QueryBuilder::for(Backup::query())
            ->where('backups.server_id', $server->id)
            ->allowedFilters(['name'])
            ->defaultSort('-created_at')
            ->allowedSorts('created_at', 'completed_at')
            ->paginate(min($request->query('per_page') ?? 20, 50));

        return fractal($backups, new BackupTransformer)->addMeta([
            'backup_count' => $this->backupRepository->getNonFailedBackups($server)->count(),
        ])->respond();
    }

    public function store(Server $server, StoreBackupRequest $request)
    {
        $backup = $this->creationService
            ->setIsLocked($request->input('locked', false))
            ->handle($server, $request->name, $request->mode, $request->compression_type);

        return fractal($backup, new BackupTransformer)->respond();
    }

    public function restore(Server $server, Backup $backup)
    {
        if (!is_null($server->status)) {
            throw new BadRequestHttpException('This server is not currently in a state that allows for a backup to be restored.');
        }

        $details = $this->detailService->getByProxmox($server);
        if ($details->state !== 'stopped') {
            throw new BadRequestHttpException('The server needs to be stopped before a backup can be restored.');
        }

        if (!$backup->successful && is_null($backup->completed_at)) {
            throw new BadRequestHttpException('This backup cannot be restored at this time: not completed or failed.');
        }

        return $this->connection->transaction(function () use ($server, $backup) {
            $server->update([
                'status' => Status::RESTORING_BACKUP->value,
            ]);

            $upid = $this->proxmoxRepository->setServer($server)->restore($backup);

            MonitorBackupRestoreJob::dispatch($server->id, $upid);
        });
    }

    public function destroy(Server $server, Backup $backup)
    {
        $this->deletionService->handle($backup);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
