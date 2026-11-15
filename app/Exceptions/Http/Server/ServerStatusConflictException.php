<?php

namespace Convoy\Exceptions\Http\Server;

use Throwable;
use Convoy\Models\Server;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ServerStatusConflictException extends ConflictHttpException
{
    /**
     * Exception thrown when the server is in an unsupported state for API access or
     * certain operations within the codebase.
     */
    public function __construct(Server $server, Throwable $previous = null)
    {
        $message = "This server's current status doesn't allow that. Try again later.";
        if ($server->isSuspended()) {
            $message = 'This server is suspended. Contact your provider or administrator for help.';
        } elseif (! $server->isInstalled()) {
            $message = "This server hasn't finished installing. Try again once it has.";
        }

        parent::__construct($message, $previous);
    }
}
