<?php

namespace Convoy\Jobs\Server;

use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Enums\Server\Status;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Services\Servers\ServerBuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessRebuildJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1000;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected ServerDeploymentData $deployment)
    {
        //
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [new WithoutOverlapping("server.rebuild-{$this->deployment->server->id}")];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ServerBuildService $buildService)
    {
        try {
            $this->deployment->server->update(['status' => Status::INSTALLING->value]);

            $buildService->rebuild($this->deployment);

            $this->deployment->server->update(['status' => null]);
        } catch (\Exception $e) {
            $this->deployment->server->update(['status' => Status::INSTALL_FAILED->value]);

            throw $e;
        }
    }
}
