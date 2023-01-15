<?php

namespace Convoy\Jobs\Server;

use Convoy\Enums\Server\Status;
use Convoy\Models\Server;
use Convoy\Services\Servers\ServerDeletionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessDeletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [new WithoutOverlapping("server.delete-{$this->serverId}")];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $serverId)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ServerDeletionService $service)
    {
        $server = Server::findOrFail($this->serverId);

        try {
            $service->handle($server);
        } catch (\Exception $e) {
            $server->update(['status' => Status::DELETION_FAILED]);

            throw $e;
        }
    }

    public function failed()
    {
        $server = Server::findOrFail($this->serverId);

        $server->update(['status' => Status::DELETION_FAILED]);
    }
}
