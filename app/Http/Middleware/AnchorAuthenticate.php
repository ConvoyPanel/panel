<?php

namespace App\Http\Middleware;

use App\Models\Anchor;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AnchorAuthenticate
{
    public function handle(Request $request, \Closure $next): mixed
    {
        $bearer = $request->bearerToken();
        [$uuid, $secret] = array_pad(explode('.', $bearer ?? '', 2), 2, null);
        $anchor = $uuid !== null ? Anchor::where('uuid', $uuid)->first() : null;

        if ($anchor === null || $secret === null || ! hash_equals($anchor->secret, $secret)) {
            throw new HttpException(401, 'Invalid Anchor credentials.', null, [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        $request->attributes->set('anchor', $anchor);

        return $next($request);
    }
}
