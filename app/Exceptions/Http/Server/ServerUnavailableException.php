<?php

namespace App\Exceptions\Http\Server;

use App\Models\Server;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class ServerUnavailableException extends ConflictHttpException
{
    /**
     * Thrown when a server cannot serve the request right now.
     *
     * Two independent reasons can put it here -- an administrative suspension, or a lifecycle
     * stage that isn't finished -- so both are checked. Suspension is reported first because
     * it is the one the operator chose and the one the user has to act on; a suspended server
     * mid-install is still, to its owner, a suspended server.
     */
    public function __construct(Server $server, ?Throwable $previous = null)
    {
        $message = 'This server is currently unavailable, please try again later.';
        if ($server->isSuspended()) {
            $message = 'This server is currently suspended and the functionality requested is unavailable.';
        } elseif (! $server->isInstalled()) {
            $message = 'This server has not yet completed its installation process, please try again later.';
        }

        parent::__construct($message, $previous);
    }
}
