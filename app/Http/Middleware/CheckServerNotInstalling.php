<?php

namespace Convoy\Http\Middleware;

use Closure;
use Convoy\Models\Server;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckServerNotInstalling
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $request->route()->parameter('server');

        if (! $server instanceof Server) {
            throw new NotFoundHttpException('Server not found');
        }

        if (! $server->installing) {
            if ($request->wantsJson()) {
                throw new AccessDeniedHttpException('Server is not installing');
            } else {
                return redirect()->route('servers.show', $server->id);
            }
        }

        return $next($request);
    }
}
