<?php

namespace Convoy\Http\Middleware\Admin\Server;

use Closure;
use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Http\Server\ServerStatusConflictException;
use Convoy\Models\Server;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateServerStatusMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $server = $request->route()->parameter('server');

        if (! $server instanceof Server) {
            throw new NotFoundHttpException('Server not found');
        }

        if ($server->status === Status::DELETING->value || $server->status === Status::DELETION_FAILED->value) {
            throw new ServerStatusConflictException($server);
        }

        return $next($request);
    }
}
