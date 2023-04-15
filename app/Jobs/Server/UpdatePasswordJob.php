<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Convoy\Services\Servers\ServerAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class UpdatePasswordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $serverId, protected string $password)
    {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new WithoutOverlapping("server.update-password-{$this->serverId}")];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ServerAuthService $service)
    {
        $server = Server::findOrFail($this->serverId);

        $service->updatePassword($server, $this->password);
    }
}
