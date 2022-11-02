<?php

namespace Convoy\Jobs\Servers;

use Convoy\Enums\Server\Status;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Services\Servers\BuildService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
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
    public function __construct(protected Server $server, protected int $templateId)
    {
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [new WithoutOverlapping($this->server->id)];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(BuildService $builder)
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $this->server->update(['status' => Status::INSTALLING->value]);

            $builder->setServer($this->server)->build(Template::findOrFail($this->templateId));

            $this->server->update(['status' => null]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
