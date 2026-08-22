<?php

namespace App\Http\Middleware;

use App\Services\Anchor\AnchorIdentityService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AnchorAuthenticate
{
    public function __construct(private AnchorIdentityService $identity) {}

    public function handle(Request $request, \Closure $next): mixed
    {
        $installation = $this->identity->resolve($request->bearerToken());

        if ($installation === null) {
            throw new HttpException(401, 'Invalid Anchor credentials.', null, [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        $request->attributes->set('anchor', $installation);

        return $next($request);
    }
}
