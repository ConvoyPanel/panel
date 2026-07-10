<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SsoController
{
    /**
     * Consume a signed SSO deep link and start a web session for the target user.
     *
     * The `signed` middleware has already verified the HMAC and expiry, so the URL is authentic
     * and unexpired here. This method enforces single use (the nonce is burned on first hit) and
     * resolves the user by uuid before logging them in.
     */
    public function consume(Request $request, string $uuid)
    {
        // Single-use: Cache::add is an atomic SETNX, so the first request wins and any replay of
        // the same (still-signed, still-unexpired) link finds the nonce already burned. The TTL
        // outlives the signature so a consumed nonce can't be reused even at the edge of expiry.
        $nonce = (string) $request->query('nonce');

        if ($nonce === '' || ! Cache::add("sso-nonce:{$nonce}", true, now()->addSeconds(config('sso.link_ttl') + 60))) {
            throw new UnauthorizedHttpException('', 'This single sign-on link has already been used or is invalid.');
        }

        $user = User::where('uuid', '=', $uuid)->first();

        if (! $user instanceof User) {
            throw new UnauthorizedHttpException('', 'The single sign-on link references an unknown user.');
        }

        Auth::loginUsingId($user->id);

        $request->session()->regenerate();

        // Audit trail: SSO bypasses password/2FA, so record every successful consumption.
        Log::channel(config('sso.audit_channel'))->info('SSO deep link consumed', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('index');
    }
}
