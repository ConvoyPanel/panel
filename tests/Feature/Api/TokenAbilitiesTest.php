<?php

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\Api\CreateApplicationTokenService;

/**
 * Scoped-ability enforcement on the Application API. Abilities were previously stored but never
 * checked (every token was effectively `*`).
 *
 * @param  list<string>  $abilities
 */
function scopedToken(array $abilities): string
{
    $admin = User::factory()->create(['root_admin' => true]);

    return app(CreateApplicationTokenService::class)->handle($admin, 'scoped', $abilities)->plainTextToken;
}

function callApp(string $token, string $method, string $uri)
{
    return test()->withHeader('Authorization', "Bearer {$token}")->json($method, $uri);
}

it('allows a read-scoped token to read its resource', function () {
    callApp(scopedToken(['locations:read']), 'GET', '/api/application/locations')->assertOk();
});

it('denies a read-scoped token from writing its resource', function () {
    // Ability is checked before validation, so the empty POST 403s on the ability, not the body.
    callApp(scopedToken(['locations:read']), 'POST', '/api/application/locations')->assertForbidden();
});

it('denies a token from a resource it has no ability for', function () {
    callApp(scopedToken(['locations:read']), 'GET', '/api/application/nodes')->assertForbidden();
});

it('treats write as implying read for the same resource', function () {
    callApp(scopedToken(['nodes:write']), 'GET', '/api/application/nodes')->assertOk();
});

it('lets a wildcard token reach everything', function () {
    $token = scopedToken(['*']);
    callApp($token, 'GET', '/api/application/locations')->assertOk();
    callApp($token, 'GET', '/api/application/nodes')->assertOk();
});

it('stores the requested abilities when minting a token', function () {
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->postJson('/api/admin/tokens', ['name' => 'scoped', 'abilities' => ['servers:read', 'nodes:write']])
        ->assertSuccessful();

    expect(PersonalAccessToken::query()->latest('id')->first()->abilities)
        ->toBe(['servers:read', 'nodes:write']);
});

it('rejects an unknown ability at mint time', function () {
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->postJson('/api/admin/tokens', ['name' => 'bad', 'abilities' => ['bogus:read']])
        ->assertStatus(422);
});
