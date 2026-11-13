<?php

namespace Convoy\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * TRUSTED_PROXIES, read through config/trustedproxy.php so it still applies
     * once the image has run `config:cache` (env() returns null after that).
     *
     * @return array<int, string>|string|null
     */
    protected function proxies()
    {
        return config('trustedproxy.proxies');
    }
}
