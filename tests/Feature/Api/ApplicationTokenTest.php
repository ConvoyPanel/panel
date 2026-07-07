<?php

use App\Enums\Api\ApiKeyType;
use App\Models\PersonalAccessToken;
use App\Models\SystemActor;
use App\Models\User;

/**
 * v2 panel-wide (application) tokens: owned by the system actor, not the minting admin, so they
 * survive that admin's deletion.
 */

function mintApplicationToken(User $admin, string $name = 'ci'): PersonalAccessToken
{
    app(\App\Services\Api\CreateApplicationTokenService::class)->handle($admin, $name);

    return PersonalAccessToken::query()->latest('id')->firstOrFail();
}

it('mints an application token owned by the system actor, not the admin', function () {
    $admin = User::factory()->create(['root_admin' => true]);

    $response = $this->actingAs($admin)
        ->postJson('/api/admin/tokens', ['name' => 'deploy-bot'])
        ->assertSuccessful();

    $token = PersonalAccessToken::query()->latest('id')->firstOrFail();

    expect($token->tokenable)->toBeInstanceOf(SystemActor::class)
        ->and($token->type)->toBe(ApiKeyType::APPLICATION)
        ->and($token->created_by)->toBe($admin->id);

    $response->assertJsonPath('data.createdBy.id', $admin->id);
});

it('keeps the token working after the creating admin is deleted', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $plain = app(\App\Services\Api\CreateApplicationTokenService::class)
        ->handle($admin, 'survivor')->plainTextToken;

    $admin->delete();

    $token = PersonalAccessToken::query()->latest('id')->firstOrFail();
    expect($token->fresh()->created_by)->toBeNull(); // audit link nulled, token intact

    // It still authenticates and reaches the admin surface.
    $this->withHeader('Authorization', "Bearer {$plain}")
        ->getJson('/api/application/locations')
        ->assertOk();
});

it('forbids an application token from managing tokens', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $plain = app(\App\Services\Api\CreateApplicationTokenService::class)
        ->handle($admin, 'no-self-manage')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$plain}")
        ->getJson('/api/application/tokens')
        ->assertForbidden();
});

it('reuses the single system actor across tokens', function () {
    $admin = User::factory()->create(['root_admin' => true]);

    mintApplicationToken($admin, 'a');
    mintApplicationToken($admin, 'b');

    expect(SystemActor::query()->count())->toBe(1)
        ->and(PersonalAccessToken::query()->distinct()->pluck('tokenable_id')->all())->toBe([SystemActor::instance()->id]);
});
