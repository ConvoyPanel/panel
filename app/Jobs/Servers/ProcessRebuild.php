<?php

namespace Convoy\Jobs\Servers;

use Activity;
use Convoy\Enums\Server\Status;
use Convoy\Facades\LogRunner;
use Convoy\Facades\LogTarget;
use Convoy\Models\ActivityLog;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Services\Activity\ActivityLogBatchService;
use Convoy\Services\Servers\BuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessRebuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3000;

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
    public function __construct(protected $serverId, protected $templateId, protected string $batchUuid, protected ?int $initialLogId)
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
        return [new WithoutOverlapping($this->serverId)];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ActivityLogBatchService $batch, BuildService $builder)
    {
        $server = Server::findOrFail($this->serverId);
        $template = Template::findOrFail($this->templateId);

        LogTarget::setSubject($server);

        $this->activity = $this->initialLogId ? ActivityLog::find($this->initialLogId) : Activity::event('server:rebuild')->runner()->log();

        try {
            $batch->transaction(function () use ($builder, $server, $template) {
                $server->update(['status' => Status::INSTALLING->value]);

                $builder->setServer($server)->rebuild($template);

                $server->update(['status' => null]);
                LogRunner::setActivity($this->activity)->end();
            }, $this->batchUuid);
        } catch (\Exception $e) {
            LogRunner::setActivity($this->activity)->error();

            throw $e;
        }
    }
}
