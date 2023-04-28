<?php

namespace Convoy\Jobs\Server;

use Convoy\Enums\Server\Status;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Services\Servers\ServerBuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class BuildServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 20;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $serverId, protected int $templateId)
    {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled, new WithoutOverlapping("server.build-{$this->serverId}")];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ServerBuildService $service)
    {
        $server = Server::findOrFail($this->serverId);
        $template = Template::findOrFail($this->templateId);

        $server->update(['status' => Status::INSTALLING->value]);

        $service->build($server, $template);
    }
}
