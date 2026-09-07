<?php

use App\Models\User;
use App\Settings\AccountSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

/** A passphrase that satisfies the policy, for the one case that gets far enough to validate. */
const POLICY_PASSPHRASE = 'exuberant plywood cascade lantern';

function setAccountPolicy(array $overrides): void
{
    $settings = app(AccountSettings::class);

    foreach ($overrides as $property => $value) {
        $settings->{$property} = $value;
    }

    $settings->save();
}

it('tells the account what it may change about itself', function () {
    setAccountPolicy(['allow_email_change' => false]);

    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->getJson('/api/client/user')
        ->assertOk()
        ->assertJsonPath('data.accountCapabilities.canChangeName', true)
        ->assertJsonPath('data.accountCapabilities.canChangeEmail', false)
        ->assertJsonPath('data.accountCapabilities.canChangePassword', true)
        ->assertJsonPath('data.accountCapabilities.canChangeAvatar', true);
});

it('refuses a name change once the operator turns it off', function () {
    setAccountPolicy(['allow_name_change' => false]);

    $user = User::factory()->create(['root_admin' => false, 'name' => 'Unchanged']);

    $this->actingAs($user)
        ->patchJson('/api/client/account/profile', ['name' => 'Something Else'])
        ->assertForbidden();

    expect($user->refresh()->name)->toBe('Unchanged');
});

it('refuses an email change once the operator turns it off', function () {
    setAccountPolicy(['allow_email_change' => false]);

    $user = User::factory()->create(['root_admin' => false, 'email' => 'old@example.com']);

    // Confirmed, so what is being tested is the policy rather than the identity gate in front of it.
    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->patchJson('/api/client/account/email', ['email' => 'new@example.com'])
        ->assertForbidden();

    expect($user->refresh()->email)->toBe('old@example.com');
});

it('refuses a password change once the operator turns it off', function () {
    setAccountPolicy(['allow_password_change' => false]);

    $user = User::factory()->create([
        'root_admin' => false,
        'password' => 'Password123!',
    ]);

    $this->actingAs($user)
        ->putJson('/api/client/account/password', [
            'current_password' => 'Password123!',
            'password' => POLICY_PASSPHRASE,
            'password_confirmation' => POLICY_PASSPHRASE,
        ])
        ->assertForbidden();

    expect(Hash::check('Password123!', $user->refresh()->password))->toBeTrue();
});

it('refuses a picture upload once the operator turns it off', function () {
    setAccountPolicy(['allow_avatar_change' => false]);

    $user = User::factory()->create(['root_admin' => false]);

    $this->actingAs($user)
        ->postJson('/api/client/account/avatar', [
            'avatar' => UploadedFile::fake()->image('face.png', 600, 600),
        ])
        ->assertForbidden();

    expect($user->refresh()->avatar_path)->toBeNull();
});

it('refuses removing a picture once the operator turns it off', function () {
    // Removal is the same capability as upload: with the switch off, what the account shows is
    // the operator's to decide, and clearing it is as much a change as replacing it.
    setAccountPolicy(['allow_avatar_change' => false]);

    $user = User::factory()->create([
        'root_admin' => false,
        'avatar_path' => 'avatars/whatever.webp',
    ]);

    $this->actingAs($user)
        ->deleteJson('/api/client/account/avatar')
        ->assertForbidden();

    expect($user->refresh()->avatar_path)->toBe('avatars/whatever.webp');
});

it('never binds an admin to the policy', function () {
    // The switches govern the self-service screen. An admin who hit a wall there would edit the
    // same row from /admin/users, so enforcing it against them restricts nothing and would read
    // as a boundary that isn't one.
    setAccountPolicy([
        'allow_name_change' => false,
        'allow_email_change' => false,
        'allow_password_change' => false,
        'allow_avatar_change' => false,
    ]);

    $admin = User::factory()->create(['root_admin' => true, 'name' => 'Before']);

    $this->actingAs($admin)
        ->getJson('/api/client/user')
        ->assertJsonPath('data.accountCapabilities.canChangeName', true)
        ->assertJsonPath('data.accountCapabilities.canChangePassword', true)
        ->assertJsonPath('data.accountCapabilities.canChangeAvatar', true);

    $this->actingAs($admin)
        ->patchJson('/api/client/account/profile', ['name' => 'After'])
        ->assertSuccessful();

    expect($admin->refresh()->name)->toBe('After');
});

it('leaves an unrestricted install exactly as it was', function () {
    $user = User::factory()->create(['root_admin' => false, 'name' => 'Before']);

    $this->actingAs($user)
        ->patchJson('/api/client/account/profile', ['name' => 'After'])
        ->assertSuccessful();

    expect($user->refresh()->name)->toBe('After');
});

it('lets an admin change a password the policy has taken away from everyone else', function () {
    Http::fake(['https://api.pwnedpasswords.com/*' => Http::response('', 200)]);

    setAccountPolicy(['allow_password_change' => false]);

    $admin = User::factory()->create([
        'root_admin' => true,
        'password' => 'Password123!',
    ]);

    $this->actingAs($admin)
        ->putJson('/api/client/account/password', [
            'current_password' => 'Password123!',
            'password' => POLICY_PASSPHRASE,
            'password_confirmation' => POLICY_PASSPHRASE,
        ])
        ->assertSuccessful();

    expect(Hash::check(POLICY_PASSPHRASE, $admin->refresh()->password))->toBeTrue();
});
