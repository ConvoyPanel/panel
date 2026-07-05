<?php

namespace App\Jobs\Server;

use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Server;
use App\Services\Servers\ServerNetworkService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SyncNetworkSettingsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        #[WithoutRelations]
        public Server $server
    ) {}

    public function middleware(): array
    {
        return [
            new SkipIfBatchCancelled,
            new WithoutOverlapping((string) $this->server->id),
        ];
    }

    /**
     * @throws RequestException
     */
    public function handle(ServerNetworkService $service): void
    {
        $service->syncSettings($this->server);
    }
}
