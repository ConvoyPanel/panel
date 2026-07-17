<?php

namespace App\Enums\Server;

enum DeploymentStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /**
     * A terminal status is an end state: once a step or deployment reaches it,
     * the lifecycle transitions refuse to move it anywhere else. This is what
     * makes an out-of-order write (a late `complete` after a `fail`, say) a
     * no-op instead of a corruption.
     */
    public function isTerminal(): bool
    {
        return $this === self::COMPLETED || $this === self::FAILED;
    }
}
