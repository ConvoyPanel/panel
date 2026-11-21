<?php

use App\Http\Middleware\ValidateCsrfToken;
use App\Services\Api\CreateAccountTokenService;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as FrameworkValidateCsrfToken;

/**
 * CSRF verification turns itself off when the app runs unit tests, so these tests swap in copies
 * of both CSRF middleware classes with that check removed. Whichever class the `web` group holds
 * then verifies tokens the way it does in production.
 */
beforeEach(function () {
    $this->app->bind(
        FrameworkValidateCsrfToken::class,
        fn (Application $app) => new class($app, $app->make(Encrypter::class)) extends FrameworkValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        },
    );

    $this->app->bind(
        ValidateCsrfToken::class,
        fn (Application $app) => new class($app, $app->make(Encrypter::class)) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        },
    );
});

it('accepts a write with a personal access token and no CSRF token', function () {
    fakeProxmox();
    [$user, , , $server] = createServerModel();
    $token = app(CreateAccountTokenService::class)->handle($user, 'ci', ['servers:write'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/client/servers/{$server->uuid}/settings/rename", [
            'name' => 'renamed',
            'hostname' => 'renamed.test',
        ])
        ->assertCreated();
});

it('rejects a session write without a CSRF token', function () {
    [$user, , , $server] = createServerModel();

    $this->actingAs($user)
        ->postJson("/api/client/servers/{$server->uuid}/settings/rename", [
            'name' => 'renamed',
            'hostname' => 'renamed.test',
        ])
        ->assertStatus(419);
});

it('rejects a session write that adds a bearer token that does not exist', function () {
    [$user, , , $server] = createServerModel();

    $this->actingAs($user)
        ->withHeader('Authorization', 'Bearer 1|not-a-real-token')
        ->postJson("/api/client/servers/{$server->uuid}/settings/rename", [
            'name' => 'renamed',
            'hostname' => 'renamed.test',
        ])
        ->assertStatus(419);
});
