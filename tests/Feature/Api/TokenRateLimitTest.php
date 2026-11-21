<?php

use App\Enums\Api\ApiKeyType;
use App\Models\User;
use App\Services\Api\CreateApplicationTokenService;

/**
 * One request budget per token, on both token APIs. The panel's browser session is not limited.
 */
beforeEach(function () {
    config(['convoy.api.rate_limit' => 3]);
});

it('limits an application token per minute', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $token = app(CreateApplicationTokenService::class)->handle($admin, 'ci')->plainTextToken;

    foreach (range(1, 3) as $_) {
        $this->withToken($token)->getJson('/api/application/locations')
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', 3);
    }

    $this->withToken($token)->getJson('/api/application/locations')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After');
});

it('limits a personal token on the client api', function () {
    $user = User::factory()->create();
    $token = $user->createToken('cli', ApiKeyType::ACCOUNT, ['servers:read'])->plainTextToken;

    foreach (range(1, 3) as $_) {
        $this->withToken($token)->getJson('/api/client/servers')->assertOk();
    }

    $this->withToken($token)->getJson('/api/client/servers')->assertTooManyRequests();
});

it('counts each token separately', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $service = app(CreateApplicationTokenService::class);
    $first = $service->handle($admin, 'one')->plainTextToken;
    $second = $service->handle($admin, 'two')->plainTextToken;

    foreach (range(1, 3) as $_) {
        $this->withToken($first)->getJson('/api/application/locations')->assertOk();
    }

    $this->app['auth']->forgetGuards();

    $this->withToken($second)->getJson('/api/application/locations')->assertOk();
});

it('does not limit the panel session', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $_) {
        $this->actingAs($user)->getJson('/api/client/servers')->assertOk();
    }
});

it('does not limit an application token restricted to an allowlist', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $token = app(CreateApplicationTokenService::class)
        ->handle($admin, 'billing', allowedNetworks: ['127.0.0.1/32'])
        ->plainTextToken;

    foreach (range(1, 5) as $_) {
        $this->withToken($token)->getJson('/api/application/locations')
            ->assertOk()
            ->assertHeaderMissing('X-RateLimit-Limit');
    }
});

it('refuses an allowlisted token from elsewhere before the limit is skipped', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $token = app(CreateApplicationTokenService::class)
        ->handle($admin, 'billing', allowedNetworks: ['203.0.113.0/24'])
        ->plainTextToken;

    $this->withToken($token)->getJson('/api/application/locations')
        ->assertForbidden()
        ->assertJsonPath('code', 'token_ip_not_allowed');
});

it('can be turned off', function () {
    config(['convoy.api.rate_limit' => 0]);
    $admin = User::factory()->create(['root_admin' => true]);
    $token = app(CreateApplicationTokenService::class)->handle($admin, 'ci')->plainTextToken;

    foreach (range(1, 5) as $_) {
        $this->withToken($token)->getJson('/api/application/locations')->assertOk();
    }
});
