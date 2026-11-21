<?php

namespace App\Http\Middleware\Admin\Server;

use App\Exceptions\Http\Server\ServerUnavailableException;
use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The admin side of the client area's readiness check, for the endpoints that act on a built
 * guest (backups, firewall, hardware, credentials, consoles).
 *
 * The client check also refuses a suspended server. This one does not: suspending a server is an
 * operator's decision, and the operator still has to be able to back it up, firewall it or reset
 * its password while it is suspended.
 */
class RequireReadyServer
{
    public function handle(Request $request, Closure $next): Response
    {
        $server = $request->route()->parameter('server');

        if (! $server instanceof Server) {
            throw new NotFoundHttpException('Server not found');
        }

        if (! $server->isReady()) {
            throw new ServerUnavailableException($server);
        }

        return $next($request);
    }
}
