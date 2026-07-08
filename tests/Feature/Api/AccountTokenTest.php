<?php

use App\Enums\Api\ApiKeyType;
use App\Models\PersonalAccessToken;
use App\Models\Server;
use App\Models\User;
use App\Services\Api\CreateAccountTokenService;

/**
 * End-user personal access tokens (ApiKeyType::ACCOUNT): bound to the user, scoped to their own
 * client resources (their servers), and unable to touch account/security management.
 */
function mintAccountToken(User $user, array $abilities = ['*'], string $name = 'ci'): string
{
    return app(CreateAccountTokenService::class)->handle($user, $name, $abilities)->plainTextToken;
}

it('mints an account token owned by the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/account/api-keys', ['name' => 'cli'])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'cli')
        ->assertJsonPath('data.type', ApiKeyType::ACCOUNT->value);

    $token = PersonalAccessToken::query()->latest('id')->firstOrFail();

    expect($token->tokenable)->toBeInstanceOf(User::class)
        ->and($token->tokenable_id)->toBe($user->id)
        ->and($token->type)->toBe(ApiKeyType::ACCOUNT);
});

it('lists only the current user\'s account tokens', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    mintAccountToken($user, name: 'mine');
    mintAccountToken($other, name: 'theirs');

    $this->actingAs($user)
        ->getJson('/api/client/account/api-keys')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'mine');
});

it('reaches the owner\'s servers with a bearer token', function () {
    $user = User::factory()->create();
    $token = mintAccountToken($user, ['servers:read']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/client/servers')
        ->assertOk();
});

it('denies a read-scoped token from a write on servers', function () {
    $user = User::factory()->create();
    $server = Server::factory()->create(['user_id' => $user->id]);
    $token = mintAccountToken($user, ['servers:read']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/client/servers/{$server->uuid}/settings/rename", ['name' => 'x'])
        ->assertForbidden();
});

it('forbids an account token from managing the account', function () {
    $user = User::factory()->create();
    $token = mintAccountToken($user, ['*']);

    // Even a wildcard token can't touch account/security or mint more tokens.
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/client/account/api-keys')
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson('/api/client/account/password', [])
        ->assertForbidden();
});

it('only lets a token delete its owner\'s tokens', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    mintAccountToken($other, name: 'victim');
    $victim = PersonalAccessToken::query()->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->deleteJson("/api/client/account/api-keys/{$victim->id}")
        ->assertNotFound();

    expect(PersonalAccessToken::query()->whereKey($victim->id)->exists())->toBeTrue();
});

it('deletes the current user\'s own token', function () {
    $user = User::factory()->create();
    mintAccountToken($user, name: 'temp');
    $token = PersonalAccessToken::query()->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->deleteJson("/api/client/account/api-keys/{$token->id}")
        ->assertNoContent();

    expect(PersonalAccessToken::query()->whereKey($token->id)->exists())->toBeFalse();
});

it('rejects an unknown ability at mint time', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/account/api-keys', ['name' => 'bad', 'abilities' => ['nodes:read']])
        ->assertJsonValidationErrors('abilities.0');
});
