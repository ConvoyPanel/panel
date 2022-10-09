<?php

namespace Convoy\Http\Middleware;

use Convoy\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckServerInstalling
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

        if (!$server instanceof Server)
        {
            throw new NotFoundHttpException('Server not found');
        }

        if ($server->installing)
        {
            if ($request->wantsJson())
            {
                throw new AccessDeniedHttpException('Server is installing');
            } else {
                return redirect()->route('servers.show.building', $server->uuidShort);
            }
        }

        return $next($request);
    }
}
