<?php

namespace App\Jobs\Backup;

use App\Exceptions\Proxmox\RequestException;
use App\Models\Backup;
use App\Services\Proxmox\Server\ProxmoxBackupClient;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

use function now;

class WaitUntilBackupIsDeletedJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(15);
    }

    public function __construct(
        #[WithoutRelations]
        public Backup $backup,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ProxmoxBackupClient $client): void
    {
        $backups = $client->setServer($this->backup->server)->getBackups($this->backup->storage);

        if (filled($backups->where('filename', $this->backup->file_name)->first())) {
            $this->release(3);
        } else {
            $this->backup->delete();
        }
    }
}
