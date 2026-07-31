<?php

namespace App\Http\Middleware\Admin\Server;

use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Http\Server\ServerUnavailableException;
use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateServerLifecycleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $server = $request->route()->parameter('server');

        if (! $server instanceof Server) {
            throw new NotFoundHttpException('Server not found');
        }

        if ($server->lifecycle === ServerLifecycle::DELETING || $server->lifecycle === ServerLifecycle::DELETION_FAILED) {
            throw new ServerUnavailableException($server);
        }

        return $next($request);
    }
}
