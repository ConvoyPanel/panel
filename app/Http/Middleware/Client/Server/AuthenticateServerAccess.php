<?php

namespace App\Http\Middleware\Client\Server;

use Closure;
use App\Exceptions\Http\Server\ServerStatusConflictException;
use App\Models\Server;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthenticateServerAccess
{
    /**
     * Routes that this middleware should not apply to if the user is an admin.
     */
    protected array $except = [];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
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
        } catch (ServerStatusConflictException $exception) {
            if ($request->routeIs('client.servers.show')) {
                return $next($request);
            }

            throw $exception;
        }

        return $next($request);
    }
}
