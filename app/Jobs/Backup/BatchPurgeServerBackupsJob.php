<?php

namespace App\Jobs\Backup;

use App\Jobs\Backup\DeleteBackupJob;
use App\Models\Backup;
use App\Models\Server;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;

class BatchPurgeServerBackupsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        #[WithoutRelations]
        public Server $server,
    ) {}

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function handle(): void
    {
        $this->server->backups()
            ->whereNull('errors')
            ->whereNotNull('completed_at')
            ->chunkById(100, function (Collection $backups) {
                $this->batch()->add($backups->map(fn (Backup $backup) => new DeleteBackupJob($backup)));
            }, column: 'id');
    }
}
