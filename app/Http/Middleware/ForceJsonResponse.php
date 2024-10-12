<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     */
    public function handle(
        Request $request,
        Closure $next,
    ): \Symfony\Component\HttpFoundation\Response {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
