<?php

namespace Convoy\Http\Middleware\Coterm;

use Convoy\Models\Coterm;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CotermAuthenticate
{
    /**
     * Coterm routes that this middleware should be skipped on.
     */
    protected array $except = [];

    /**
     * Check if a request from the daemon can be properly attributed back to a single node instance.
     *
     * @throws HttpException
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        if (is_null($bearer = $request->bearerToken())) {
            throw new HttpException(
                401, 'Access to this endpoint must include an Authorization header.', null,
                ['WWW-Authenticate' => 'Bearer'],
            );
        }

        $parts = explode('|', $bearer);
        // Ensure that all the correct parts are provided in the header.
        if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
            throw new BadRequestHttpException(
                'The Authorization header provided was not in a valid format.',
            );
        }

        try {
            $coterm = Coterm::where('token_id', $parts[0])->firstOrFail();

            if (hash_equals($coterm->token, $parts[1])) {
                return $next($request);
            }
        } catch (ModelNotFoundException) {
            // Do nothing, we don't want to expose a node not existing at all.
        }

        throw new HttpException(401);
    }
}
