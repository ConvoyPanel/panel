<?php

namespace Convoy\Http\Middleware\Client\Server;

use Closure;
use Convoy\Models\Server;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Http\Server\ServerStatusConflictException;
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

        if (! $server instanceof Server) {
            throw new NotFoundHttpException('Server not found');
        }

        if ($user->id !== $server->user_id && ! $user->root_admin) {
            throw new NotFoundHttpException('Server not found');
        }

        try {
            $server->validateCurrentState();
        } catch (ServerStatusConflictException $exception) {
            if ($request->routeIs('client.servers.show')) {
                return $next($request);
            }

            // A failed install can be retried: reinstalling is the retry, and
            // the form needs the template list. Every other state stays blocked.
            if (
                $server->status === Status::INSTALL_FAILED->value
                && $request->routeIs('client.servers.settings.template-groups', 'client.servers.settings.reinstall')
            ) {
                return $next($request);
            }

            throw $exception;
        }

        return $next($request);
    }
}
