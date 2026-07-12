<?php

use App\Auth\Socialite\OidcProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Build an OidcProvider whose HTTP client returns the given queued responses, so we can drive
 * the discovery + userinfo flow without a live IdP.
 */
function oidcProvider(array $responses, ?Request $request = null): array
{
    $mock = new MockHandler($responses);
    $client = new Client(['handler' => HandlerStack::create($mock)]);

    $request ??= Request::create('/api/auth/oauth/oidc/callback');

    if (! $request->hasSession()) {
        $request->setLaravelSession(app('session.store'));
    }

    $provider = new OidcProvider(
        $request,
        'client-id',
        'client-secret',
        'https://convoy.test/api/auth/oauth/oidc/callback',
    );
    $provider->setHttpClient($client);

    return [$provider, $mock];
}

beforeEach(function () {
    Cache::flush();
    config([
        'services.oidc.base_url' => 'https://idp.example.com',
        'services.oidc.client_id' => 'client-id',
        'services.oidc.client_secret' => 'client-secret',
        'services.oidc.redirect' => '/api/auth/oauth/oidc/callback',
    ]);
});

it('builds the authorize URL from the discovery document', function () {
    [$provider] = oidcProvider([
        new Response(200, [], json_encode([
            'authorization_endpoint' => 'https://idp.example.com/authorize',
            'token_endpoint' => 'https://idp.example.com/token',
            'userinfo_endpoint' => 'https://idp.example.com/userinfo',
        ])),
    ]);

    $url = $provider->redirect()->getTargetUrl();

    expect($url)->toStartWith('https://idp.example.com/authorize?')
        ->and($url)->toContain('client_id=client-id')
        ->and($url)->toContain('scope=openid'); // driver default includes openid
});

it('prefers an explicit endpoint override over discovery', function () {
    config(['services.oidc.auth_url' => 'https://idp.example.com/custom/authorize']);

    // No discovery response queued: an override must mean discovery is never fetched.
    [$provider] = oidcProvider([]);

    expect($provider->redirect()->getTargetUrl())
        ->toStartWith('https://idp.example.com/custom/authorize?');
});

it('caches the discovery document across calls', function () {
    [$provider, $mock] = oidcProvider([
        new Response(200, [], json_encode([
            'authorization_endpoint' => 'https://idp.example.com/authorize',
        ])),
    ]);

    $provider->redirect();
    $provider->redirect(); // second call must hit the cache, not the (now empty) mock queue

    expect($mock->count())->toBe(0); // exactly one queued response was consumed
});

it('maps standard OIDC claims and keeps email_verified in the raw payload', function () {
    $request = Request::create('/api/auth/oauth/oidc/callback', 'GET', [
        'code' => 'auth-code',
        'state' => 'the-state',
    ]);

    [$provider] = oidcProvider([
        // 1) discovery, 2) token exchange, 3) userinfo
        new Response(200, [], json_encode([
            'token_endpoint' => 'https://idp.example.com/token',
            'userinfo_endpoint' => 'https://idp.example.com/userinfo',
        ])),
        new Response(200, [], json_encode(['access_token' => 'at', 'token_type' => 'Bearer'])),
        new Response(200, [], json_encode([
            'sub' => 'user-123',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'email_verified' => true,
            'preferred_username' => 'jane',
            'picture' => 'https://idp.example.com/jane.png',
        ])),
    ], $request);

    $request->session()->put('state', 'the-state');

    $user = $provider->user();

    expect($user->getId())->toBe('user-123')
        ->and($user->getName())->toBe('Jane Doe')
        ->and($user->getEmail())->toBe('jane@example.com')
        ->and($user->getNickname())->toBe('jane')
        ->and($user->getRaw()['email_verified'])->toBeTrue();
});
