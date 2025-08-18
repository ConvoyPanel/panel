<?php

namespace App\Jobs\Server;

use App\Models\Server;
use App\Services\Servers\ServerBuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class WaitUntilVmIsDeletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(30);
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled()];
    }

    public function __construct(
        #[WithoutRelations]
        public Server $server,
    )
    {
    }

    public function handle(ServerBuildService $service): void
    {
        $isDeleted = $service->isVmDeleted($this->server);

        if (! $isDeleted) {
            $this->release(3);
        }
    }
}
