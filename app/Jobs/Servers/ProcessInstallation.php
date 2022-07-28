<?php

namespace App\Jobs\Servers;

use App\Models\Server;
use App\Models\Template;
use App\Services\Servers\InstallService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInstallation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $templateId, protected int $serverId, protected string $target, protected int $vmid)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $server = Server::find($this->serverId);

        $server->update(['is_installing' => true]);

        (new InstallService)->setServer(Template::find($this->templateId)->server)->install($this->vmid, $this->target);

        $server->update(['is_installing' => false]);
    }
}
