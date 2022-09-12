<?php

namespace App\Jobs\Servers;

use App\Facades\LogTarget;
use App\Models\Objects\Server\ServerDeploymentObject;
use App\Models\Server;
use App\Models\Template;
use App\Services\Activity\ActivityLogBatchService;
use App\Services\Servers\InstallService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ProcessBuild implements ShouldQueue
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
    public function __construct(protected Server $server, protected ServerDeploymentObject $deployment, protected ?string $batchUuid)
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ActivityLogBatchService $batch, InstallService $installer)
    {
        Assert::isInstanceOf($this->server, Server::class);

        LogTarget::setSubject($this->server);

        $batch->transaction(function () use ($installer) {
            $this->server->update(['installing' => true]);

            $installer->setServer($this->server)->build(Template::find(Arr::get($this->deployment, 'template_id')), $this->deployment);

            $this->server->update(['installing' => false]);
        }, $this->batchUuid);
    }
}
