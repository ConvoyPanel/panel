<?php

namespace App\Http\Middleware\Client\Server;

use App\Exceptions\Http\Server\ServerStatusConflictException;
use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthenticateServerAccess
{
    /**
     * Routes that this middleware should not apply to regardless of the status.
     */
    protected array $except = [
        'client.servers.show',
        'client.servers.show.deployment',
        'client.servers.show.retry-installation',
        'client.servers.template-groups.index',
        'client.servers.show.reinstall'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $server = $request->route()->parameter('server');

        if (! $server instanceof Server) {
            throw new NotFoundHttpException('Server not found');
        }

        if ($user->id !== $server->user_id && ! $user->root_admin) {
            throw new NotFoundHttpException('Server not found');
        }

        if (! $server->isReady() && ! $request->routeIs($this->except)) {
            throw new ServerStatusConflictException($server);
        }

        return $next($request);
    }
}
