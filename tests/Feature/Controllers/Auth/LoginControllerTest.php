<?php

use Carbon\CarbonImmutable;
use Convoy\Models\User;
use Convoy\Services\Api\JWTService;

it('authenticates a user with a valid token', function () {
    $user = User::factory()->create();

    $token = app(JWTService::class)
        ->setExpiresAt(CarbonImmutable::now()->addSeconds(60))
        ->setUser($user)
        ->handle(config('app.key'), config('app.url'), $user->uuid);

    $response = $this->get('/authenticate?token=' . $token->toString());

    $response->assertRedirect(route('index'));
    $this->assertAuthenticatedAs($user);
});

it('rejects a token signed with a different key', function () {
    $user = User::factory()->create();

    $token = app(JWTService::class)
        ->setExpiresAt(CarbonImmutable::now()->addSeconds(60))
        ->setUser($user)
        ->handle('wrong-key-that-is-long-enough-for-hmac', config('app.url'), $user->uuid);

    $response = $this->get('/authenticate?token=' . $token->toString());

    $response->assertUnauthorized();
    $this->assertGuest();
});

it('rejects a token with a tampered payload', function () {
    $user = User::factory()->create();

    $token = app(JWTService::class)
        ->setExpiresAt(CarbonImmutable::now()->addSeconds(60))
        ->setUser($user)
        ->handle(config('app.key'), config('app.url'), $user->uuid);

    // Tamper with the payload by decoding, modifying, and re-encoding without re-signing
    $parts = explode('.', $token->toString());
    $payload = json_decode(base64_decode($parts[1]), true);
    $victim = User::factory()->create();
    $payload['user_uuid'] = $victim->uuid;
    $parts[1] = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $tamperedToken = implode('.', $parts);

    $response = $this->get('/authenticate?token=' . $tamperedToken);

    $response->assertUnauthorized();
    $this->assertGuest();
});

it('rejects an expired token', function () {
    $user = User::factory()->create();

    $token = app(JWTService::class)
        ->setExpiresAt(CarbonImmutable::now()->subSeconds(60))
        ->setUser($user)
        ->handle(config('app.key'), config('app.url'), $user->uuid);

    $response = $this->get('/authenticate?token=' . $token->toString());

    $response->assertUnauthorized();
    $this->assertGuest();
});

it('rejects a completely fabricated token', function () {
    $response = $this->get('/authenticate?token=eyJhbGciOiJIUzI1NiJ9.eyJ1c2VyX3V1aWQiOiJmYWtlIn0.invalidsignature');

    $response->assertUnauthorized();
    $this->assertGuest();
});
