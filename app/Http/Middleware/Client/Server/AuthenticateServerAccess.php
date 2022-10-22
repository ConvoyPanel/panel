<?php

namespace Convoy\Http\Middleware\Client\Server;

use Closure;
use Convoy\Exceptions\Http\Server\ServerStateConflictException;
use Convoy\Models\Server;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthenticateServerAccess
{

    /**
     * Routes that this middleware should not apply to if the user is an admin.
     */
    protected array $except = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $server = $request->route()->parameter('server');

        if (!$server instanceof Server) {
            throw new NotFoundHttpException('Server not found');
        }

        if ($user->id !== $server->user_id && !$user->root_admin) {
            throw new NotFoundHttpException('Server not found');
        }

        try {
            $server->validateCurrentState();

            if ($request->routeIs(['servers.show.building', 'servers.show.suspended'])) {
                return redirect()->route('servers.show', $server);
            }
        } catch (ServerStateConflictException $exception) {
            if ($server->isInstalling() && !$request->routeIs('servers.show.building')) {
                //throw $exception; // for v3
                return redirect()->route('servers.show.building', $server->id);
            }

            if ($server->isSuspended() && !$request->routeIs('servers.show.suspended')) {
                //throw $exception; // for v3
                return redirect()->route('servers.show.suspended', $server->id);
            }

            if ($request->routeIs(['servers.show.building', 'servers.show.suspended'])) {
                return $next($request);
            }

            throw $exception;

            /* if (!$request->routeIs(['servers.show.building'])) {
                if ($server->isSuspended() && !$request->routeIs('servers.show.suspended')) {
                    //throw $exception; // for v3
                    return redirect()->route('servers.show.suspended', $server->id);
                }

                if ($server->isInstalling()) {
                    //throw $exception; // for v3
                    return redirect()->route('servers.show.building', $server->id);
                }

                // TODO: users can still view building screen on accident if the server is suspended and vice versa
                if (!$user->root_admin || !$request->routeIs($this->except) && !$request->routeIs(['servers.show.suspended', 'servers.show.building'])) {
                    throw $exception;
                }
            } */
        }

        return $next($request);
    }
}
