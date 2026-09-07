<?php

use App\Enums\Api\ApiKeyType;
use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\OAuthConnection;
use App\Models\Passkey;
use App\Models\SSHKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** An SSH key on someone's account, without going through the client-side request validation. */
function sshKeyFor(User $user, string $name = 'laptop'): SSHKey
{
    return $user->sshKeys()->create([
        'name' => $name,
        'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI'.str_repeat('a', 20).' '.$name,
    ]);
}

/**
 * Inserted straight into the table: the package's `data` attribute casts through a WebAuthn
 * serializer, and none of what is under test cares what a real credential blob looks like.
 */
function passkeyFor(User $user, string $name = 'yubikey'): Passkey
{
    $id = DB::table('passkeys')->insertGetId([
        'user_id' => $user->id,
        'name' => $name,
        'credential_id' => bin2hex(random_bytes(16)),
        'data' => json_encode(['stub' => true]),
        'created_at' => now(),
    ]);

    return Passkey::query()->findOrFail($id);
}

it('lists the account keys but not application tokens', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create();

    $target->createToken('deploy-bot', ApiKeyType::ACCOUNT);
    $target->createToken('panel integration', ApiKeyType::APPLICATION);

    $response = $this->actingAs($admin)
        ->getJson("/api/admin/users/{$target->id}/api-keys")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.name'))->toBe('deploy-bot');
});

it('revokes an SSH key and records it against the account it belonged to', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create();
    $key = sshKeyFor($target);

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$target->id}/ssh-keys/{$key->id}")
        ->assertNoContent();

    expect(SSHKey::query()->whereKey($key->id)->exists())->toBeFalse();

    $entry = AuditLog::query()
        ->where('event', '=', AuditEvent::ACCOUNT_SSH_KEY_DELETED)
        ->latest('id')
        ->first();

    // Two different people: the admin acted, the target owns the history.
    expect($entry)->not->toBeNull()
        ->and($entry->actor_id)->toBe($admin->id)
        ->and($entry->subject_id)->toBe($target->id)
        ->and($entry->properties['name'])->toBe('laptop');
});

it('revokes a passkey and an oauth connection', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create();

    $passkey = passkeyFor($target);
    $connection = OAuthConnection::query()->create([
        'user_id' => $target->id,
        'provider' => 'github',
        'provider_id' => '99',
    ]);

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$target->id}/passkeys/{$passkey->id}")
        ->assertNoContent();
    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$target->id}/oauth-connections/{$connection->id}")
        ->assertNoContent();

    expect(Passkey::query()->whereKey($passkey->id)->exists())->toBeFalse()
        ->and(OAuthConnection::query()->whereKey($connection->id)->exists())->toBeFalse();
});

it('404s a credential that belongs to a different account', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create();
    $bystander = User::factory()->create();

    $key = sshKeyFor($bystander, 'not-theirs');

    // Scope binding, not a controller check: the id resolves against `$user->sshKeys()`, so a
    // stranger's key id is indistinguishable from one that never existed.
    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$target->id}/ssh-keys/{$key->id}")
        ->assertNotFound();

    expect(SSHKey::query()->whereKey($key->id)->exists())->toBeTrue();
});

it('404s an application token addressed as an account key', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create();

    $token = $target->createToken('panel integration', ApiKeyType::APPLICATION);

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$target->id}/api-keys/{$token->accessToken->id}")
        ->assertNotFound();
});

it('refuses to touch the signed-in admin own credentials', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $key = sshKeyFor($admin, 'my-own');

    // The client-side route for this sits behind identity confirmation; reaching it through the
    // admin surface would be a way around that, so it is refused outright.
    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$admin->id}/ssh-keys/{$key->id}")
        ->assertStatus(400);

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$admin->id}/two-factor")
        ->assertStatus(400);

    expect(SSHKey::query()->whereKey($key->id)->exists())->toBeTrue();
});

it('disables an account two-factor authenticator', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-one'])),
        'two_factor_confirmed_at' => now(),
    ]);

    expect($target->hasEnabledTwoFactorAuthentication())->toBeTrue();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$target->id}/two-factor")
        ->assertNoContent();

    expect($target->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();

    // Recorded through Fortify's own event, the same as any other two-factor change.
    expect(
        AuditLog::query()
            ->where('event', '=', AuditEvent::ACCOUNT_TWO_FACTOR_DISABLED)
            ->where('subject_id', '=', $target->id)
            ->exists()
    )->toBeTrue();
});

it('keeps non-admins out', function () {
    $user = User::factory()->create(['root_admin' => false]);
    $target = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/admin/users/{$target->id}/ssh-keys")
        ->assertForbidden();
});
