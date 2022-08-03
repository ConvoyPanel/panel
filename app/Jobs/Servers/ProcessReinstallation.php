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

class ProcessReinstallation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected $serverId, protected $templateId)
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
        $template = Template::find($this->templateId)->server;

        $server->update(['is_installing' => true]);

        (new InstallService)->setServer($server)->reinstall($template);

        $server->update(['is_installing' => false]);
    }
}
