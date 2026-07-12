<?php

namespace App\Jobs\Server;

use App\Console\Commands\Server\UpdateRateLimitsCommand;
use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Server;
use App\Services\Nodes\ServerRateLimitsSyncService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * Reconciles a single server's NIC network state (speed cap / overage penalty).
 * Fanned out one-per-server by {@see UpdateRateLimitsCommand}
 * so servers sync concurrently with per-server retries and isolation, rather than
 * one blocking loop over the whole node.
 */
class SyncServerRateLimitJob implements ShouldQueue
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
     * @throws ConfigModifiedException
     */
    public function handle(ServerRateLimitsSyncService $service): void
    {
        $service->sync($this->server);
    }
}
