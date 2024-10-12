<?php

namespace Convoy\Http\Middleware\Client\Server;

use Closure;
use Convoy\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServerInstalled
{
    /**
     * Checks that the server is installed before allowing access through the route.
     */
    public function handle(Request $request, Closure $next,
    ): \Symfony\Component\HttpFoundation\Response {
        /** @var Server|null $server */
        $server = $request->route()->parameter('server');

        if (! $server instanceof Server) {
            throw new NotFoundHttpException(
                'No server resource was located in the request parameters.',
            );
        }

        if (! $server->isInstalled()) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                'Access to this resource is not allowed due to the current installation state.',
            );
        }

        return $next($request);
    }
}
