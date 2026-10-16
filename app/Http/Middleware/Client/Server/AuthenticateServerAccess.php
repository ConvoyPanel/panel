<?php

namespace App\Http\Middleware\Client\Server;

use App\Enums\Admin\AdminPermission;
use App\Exceptions\Http\Server\ServerUnavailableException;
use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthenticateServerAccess
{
    /**
     * Routes that this middleware should not apply to regardless of the server's condition.
     */
    protected array $except = [
        'client.servers.show',
        'client.servers.show.deployment',
        'client.servers.show.retry-installation',
        'client.servers.template-groups.index',
        'client.servers.show.reinstall',
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

        // 404 rather than 403 for all three misses: whether a server exists is itself something
        // only the people who can reach it should learn.
        $reachable = $user->id === $server->user_id
            || $server->subuserFor($user) !== null
            || $user->hasAdminPermission(AdminPermission::SERVERS_READ);

        if (! $reachable) {
            throw new NotFoundHttpException('Server not found');
        }

        // Both axes, spelled out. Suspension used to be a lifecycle value, so `isReady()`
        // alone happened to cover it; now that they are separate columns, dropping either
        // check silently opens the API up to one of the two conditions.
        if (($server->isSuspended() || ! $server->isReady()) && ! $request->routeIs($this->except)) {
            throw new ServerUnavailableException($server);
        }

        return $next($request);
    }
}
