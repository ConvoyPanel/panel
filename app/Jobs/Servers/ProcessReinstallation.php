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
    public function __construct(protected $serverId, protected $templateId)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(InstallService $installer)
    {
        $server = Server::find($this->serverId);
        $template = Template::find($this->templateId);

        $server->update(['installing' => true]);

        $installer->setServer($server)->reinstall($template);

        $server->update(['installing' => false]);
    }
}
