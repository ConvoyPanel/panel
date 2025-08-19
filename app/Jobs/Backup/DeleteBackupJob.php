<?php

namespace App\Jobs\Backup;

use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Backup;
use App\Repositories\Proxmox\Server\ProxmoxBackupRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;

class DeleteBackupJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 20;

    public function __construct(
        #[WithoutRelations]
        public Backup $backup
    ) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ProxmoxBackupRepository $repository): void
    {
        $repository->setServer($this->backup->server)->delete($this->backup);

        $this->batch()->add(new WaitUntilBackupIsDeletedJob($this->backup));
    }
}
