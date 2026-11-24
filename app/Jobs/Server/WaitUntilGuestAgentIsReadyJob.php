<?php

namespace Convoy\Jobs\Server;

use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxGuestAgentRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Holds a build chain until the QEMU guest agent inside the VM answers.
 *
 * A VM reports "running" as soon as it's powered on, but the guest agent only
 * starts once the OS has booted, which on Windows takes minutes. A password
 * set through the agent before then fails.
 *
 * If the agent still doesn't answer after the last attempt, the chain goes on
 * as it did before this job existed, so a template without the agent doesn't
 * fail to install.
 */
class WaitUntilGuestAgentIsReadyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const RELEASE_SECONDS = 5;

    /**
     * About 15 minutes of waiting at RELEASE_SECONDS between attempts.
     */
    public int $tries = 180;

    public int $timeout = 60;

    public function __construct(protected int $serverId)
    {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled()];
    }

    public function handle(ProxmoxGuestAgentRepository $repository): void
    {
        $server = Server::findOrFail($this->serverId);

        try {
            $repository->setServer($server)->guestAgentOs();

            return;
        } catch (\Exception) {
            // Proxmox answers with an error until the agent is running.
        }

        if ($this->attempts() >= $this->tries) {
            Log::warning('The guest agent did not answer; continuing the build without it.', [
                'server_id' => $this->serverId,
            ]);

            return;
        }

        $this->release(self::RELEASE_SECONDS);
    }
}
