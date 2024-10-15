<?php

namespace App\Exceptions\Http\Server;

use App\Models\Server;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class ServerStatusConflictException extends ConflictHttpException
{
    /**
     * Exception thrown when the server is in an unsupported state for API access or
     * certain operations within the codebase.
     */
    public function __construct(Server $server, Throwable $previous = null)
    {
        $message = 'This server is currently in an unsupported status, please try again later.';
        if ($server->isSuspended()) {
            $message = 'This server is currently suspended and the functionality requested is unavailable.';
        } elseif (! $server->isInstalled()) {
            $message = 'This server has not yet completed its installation process, please try again later.';
        }

        parent::__construct($message, $previous);
    }
}
