<?php

namespace App\Jobs\Servers;

use Activity;
use App\Facades\LogRunner;
use App\Facades\LogTarget;
use App\Models\ActivityLog;
use App\Models\Server;
use App\Models\Template;
use App\Services\Activity\ActivityLogBatchService;
use App\Services\Servers\BuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessRebuild implements ShouldQueue
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

    protected ActivityLog $activity;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected $serverId, protected $templateId, protected string $batchUuid)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ActivityLogBatchService $batch, BuildService $builder)
    {
        $server = Server::find($this->serverId);
        $template = Template::find($this->templateId);

        LogTarget::setSubject($server);

        $batch->transaction(function () use ($builder, $server, $template) {
            $server->update(['installing' => true]);
            $this->activity = Activity::event('server:rebuild')->runner()->log();

            $builder->setServer($server)->rebuild($template);

            $server->update(['installing' => false]);
            LogRunner::setActivity($this->activity)->end();
        }, $this->batchUuid);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        LogRunner::setActivity($this->activity)->error();
    }
}
