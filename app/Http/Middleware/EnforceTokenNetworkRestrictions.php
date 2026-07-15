<?php

namespace App\Http\Middleware;

use App\Enums\Api\ApiKeyType;
use App\Exceptions\Http\Api\TokenIpNotAllowedException;
use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnforceTokenNetworkRestrictions
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken || $token->type !== ApiKeyType::APPLICATION) {
            return $next($request);
        }

        $allowedNetworks = $token->allowed_networks ?? [];

        if ($allowedNetworks === []) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if ($clientIp === null || ! IpUtils::checkIp($clientIp, $allowedNetworks)) {
            throw new TokenIpNotAllowedException;
        }

        return $next($request);
    }
}
