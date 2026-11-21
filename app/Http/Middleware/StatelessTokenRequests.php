<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a token request to the client API from starting a session.
 *
 * The client API sits in the web group so the SPA's cookie session works, which used to mean
 * every bearer-token call also got a session: a row in the session store per request, and a
 * session and XSRF cookie on every response that a script has no use for. A request that
 * carries a bearer token and no session cookie is not a browser session, so it is given an
 * in-memory one that is thrown away, and the cookies are taken off the way out.
 *
 * A request with both a token and a session cookie is left alone: that is a browser, and the
 * guard decides which credential wins.
 */
class StatelessTokenRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionCookie = (string) config('session.cookie');

        if ($request->bearerToken() === null || $request->cookies->has($sessionCookie)) {
            return $next($request);
        }

        $driver = config('session.driver');
        config(['session.driver' => 'array']);

        try {
            $response = $next($request);
        } finally {
            // Restored so a long-running worker does not carry the swap into the next request.
            config(['session.driver' => $driver]);
        }

        foreach ($response->headers->getCookies() as $cookie) {
            if (in_array($cookie->getName(), [$sessionCookie, 'XSRF-TOKEN'], true)) {
                $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            }
        }

        return $response;
    }
}
