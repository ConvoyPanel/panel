<?php

use App\Models\SSHKey;
use App\Models\User;

// A real, well-formed ed25519 public key (algorithm name echoed inside the base64 blob).
const VALID_KEY = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAINZ0iyF9j8kT1Z7Xwq9pT5Q4tqUeq0m5Yy8VqQ2Jb3n user@example';

it('adds a key to the keychain', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/account/ssh-keys', [
            'name' => 'laptop',
            'public_key' => VALID_KEY,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'laptop');

    expect($user->sshKeys()->count())->toBe(1);
});

it('rejects a malformed public key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/account/ssh-keys', [
            'name' => 'bad',
            'public_key' => 'ssh-ed25519 not-base64!!!',
        ])
        ->assertJsonValidationErrors('public_key');
});

it('rejects a key whose embedded algorithm is mismatched', function () {
    $user = User::factory()->create();

    // Valid base64 but the blob declares ssh-ed25519, not ssh-rsa.
    $body = explode(' ', VALID_KEY)[1];

    $this->actingAs($user)
        ->postJson('/api/client/account/ssh-keys', [
            'name' => 'spoofed',
            'public_key' => "ssh-rsa {$body}",
        ])
        ->assertJsonValidationErrors('public_key');
});

it('lists only the current user\'s keys', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $user->sshKeys()->create(['name' => 'mine', 'public_key' => VALID_KEY]);
    $other->sshKeys()->create(['name' => 'theirs', 'public_key' => VALID_KEY]);

    $this->actingAs($user)
        ->getJson('/api/client/account/ssh-keys')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'mine');
});

it('only lets a user delete their own key', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $victim = $other->sshKeys()->create(['name' => 'victim', 'public_key' => VALID_KEY]);

    $this->actingAs($user)
        ->deleteJson("/api/client/account/ssh-keys/{$victim->id}")
        ->assertNotFound();

    expect(SSHKey::query()->whereKey($victim->id)->exists())->toBeTrue();
});

it('deletes the user\'s own key', function () {
    $user = User::factory()->create();
    $key = $user->sshKeys()->create(['name' => 'temp', 'public_key' => VALID_KEY]);

    $this->actingAs($user)
        ->deleteJson("/api/client/account/ssh-keys/{$key->id}")
        ->assertNoContent();

    expect(SSHKey::query()->whereKey($key->id)->exists())->toBeFalse();
});
