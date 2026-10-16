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
use Convoy\Jobs\Concerns\LocksPerSubject;
use Illuminate\Queue\SerializesModels;

class BuildServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LocksPerSubject;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(protected int $serverId, protected int $templateId)
    {
    }

    /**
     * Wait before retrying a failed Proxmox call instead of retrying at once.
     */
    public function backoff(): array
    {
        return [5, 15];
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled(), $this->lockPerSubject($this->serverId)];
    }

    public function handle(ServerBuildService $service): void
    {
        $server = Server::findOrFail($this->serverId);
        $template = Template::findOrFail($this->templateId);

        $server->update(['status' => Status::INSTALLING->value]);

        $service->build($server, $template);
    }
}
