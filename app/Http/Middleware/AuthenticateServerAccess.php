<?php

namespace App\Http\Middleware;

use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticateServerAccess
{
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

        if (!$server instanceof Server)
        {
            throw new NotFoundHttpException('Server not found');
        }

        if ($user->id !== $server->user_id && !$user->root_admin)
        {
            throw new UnauthorizedHttpException('Server not found');
        }

        return $next($request);
    }
}