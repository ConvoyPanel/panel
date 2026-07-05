<?php

use App\Enums\Api\ApiKeyType;
use App\Models\User;

/**
 * The Application API (/api/application, Sanctum Bearer tokens) shares the exact
 * same route definitions as the admin panel (/api/admin, web session). These
 * tests lock in the two guarantees that make that sharing safe.
 */

function applicationToken(bool $admin = true): string
{
    $user = User::factory()->create(['root_admin' => $admin]);

    return $user->createToken('test', ApiKeyType::APPLICATION)->plainTextToken;
}

it('lets an application token reach shared admin endpoints', function () {
    $token = applicationToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/application/locations')
        ->assertOk();
});

it('forbids an application token from managing tokens', function () {
    $token = applicationToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/application/tokens')
        ->assertForbidden();
});

it('still lets a session admin manage tokens', function () {
    $user = User::factory()->create(['root_admin' => true]);

    $this->actingAs($user)
        ->getJson('/api/admin/tokens')
        ->assertOk();
});

it('rejects a non-admin application token', function () {
    $token = applicationToken(admin: false);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/application/locations')
        ->assertForbidden();
});
