<?php

use App\Enums\Admin\AdminPermission;
use App\Enums\Server\ServerPermission;
use App\Enums\User\UserType;
use App\Models\User;
use App\Models\UserInvite;
use App\Notifications\UserInvited;
use App\Settings\MailSettings;
use App\Settings\PermissionSettings;
use Illuminate\Support\Facades\Notification;

/**
 * Handing out, editing and taking back access to a server.
 */
beforeEach(function () {
    fakeProxmox();
    Notification::fake();

    [$this->owner, , , $this->server] = createServerModel();
    $this->endpoint = "/api/client/servers/{$this->server->uuid}/subusers";
});

it('shares a server with an account that already exists', function () {
    $invitee = User::factory()->create(['email' => 'ada@example.test']);

    $this->actingAs($this->owner)
        ->postJson($this->endpoint, [
            'email' => 'ada@example.test',
            'permissions' => ['power.start', 'backup.read'],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.email', 'ada@example.test')
        ->assertJsonPath('data.permissions', ['power.start', 'backup.read'])
        ->assertJsonPath('data.isPending', false);

    expect($this->server->subusers()->where('user_id', $invitee->id)->exists())->toBeTrue();

    // Nothing was created, so nothing was invited.
    Notification::assertNothingSent();
});

it('finds an existing account whatever the caller capitalised', function () {
    User::factory()->create(['email' => 'ada@example.test']);

    $this->actingAs($this->owner)
        ->postJson($this->endpoint, ['email' => 'Ada@Example.Test', 'permissions' => []])
        ->assertSuccessful();

    expect(User::query()->count())->toBe(2);
});

it('refuses an unknown address while guest accounts are switched off', function () {
    $this->actingAs($this->owner)
        ->postJson($this->endpoint, ['email' => 'stranger@example.test', 'permissions' => []])
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'No account exists for that email address.');

    expect(User::query()->where('email', 'stranger@example.test')->exists())->toBeFalse();
});

it('creates and invites a guest when guest accounts are switched on', function () {
    app(PermissionSettings::class)->fill(['allow_guest_accounts' => true])->save();

    $this->actingAs($this->owner)
        ->postJson($this->endpoint, [
            'email' => 'stranger@example.test',
            'permissions' => ['power.start'],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.isPending', true)
        // The link comes back the once, the same way admin user creation returns it: mail is not
        // proof of delivery and plenty of installs have no SMTP.
        ->assertJsonStructure(['invite' => ['link', 'expiresAt', 'emailed']]);

    $guest = User::query()->where('email', 'stranger@example.test')->firstOrFail();

    expect($guest->type)->toBe(UserType::GUEST)
        ->and($guest->isAdmin())->toBeFalse()
        ->and(UserInvite::query()->where('user_id', $guest->id)->exists())->toBeTrue();
});

it('emails the invitation when mail is configured', function () {
    app(PermissionSettings::class)->fill(['allow_guest_accounts' => true])->save();

    // `MailConfigurator::isConfigured()` reads MailSettings, not the mail config:
    // with `mail.default` on smtp it is the stored host that decides, and setting
    // `mail.mailers.smtp.host` left that empty. The test then passed only where
    // the database happened to have mail configured already, and failed on CI's
    // fresh one.
    config(['mail.default' => 'smtp']);
    app(MailSettings::class)->fill(['host' => 'mail.example.test'])->save();

    $this->actingAs($this->owner)
        ->postJson($this->endpoint, ['email' => 'stranger@example.test', 'permissions' => []])
        ->assertSuccessful();

    $guest = User::query()->where('email', 'stranger@example.test')->firstOrFail();

    Notification::assertSentTo($guest, UserInvited::class);
});

it('refuses to share a server with its own owner', function () {
    $this->actingAs($this->owner)
        ->postJson($this->endpoint, ['email' => $this->owner->email, 'permissions' => []])
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'This account already owns the server.');
});

it('refuses to share the same server twice with one account', function () {
    $invitee = subuser($this->server, []);

    $this->actingAs($this->owner)
        ->postJson($this->endpoint, ['email' => $invitee->email, 'permissions' => []])
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'This server is already shared with that account.');
});

it('rejects a permission that is not in the catalog', function () {
    $invitee = User::factory()->create();

    $this->actingAs($this->owner)
        ->postJson($this->endpoint, [
            'email' => $invitee->email,
            'permissions' => ['servers.delete-everything'],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('permissions.0');
});

it('edits and revokes a grant', function () {
    $invitee = subuser($this->server, [ServerPermission::POWER_START]);
    $grant = $this->server->subusers()->where('user_id', $invitee->id)->firstOrFail();

    $this->actingAs($this->owner)
        ->patchJson("{$this->endpoint}/{$grant->uuid}", ['permissions' => ['backup.read']])
        ->assertOk()
        ->assertJsonPath('data.permissions', ['backup.read']);

    $this->actingAs($invitee)
        ->postJson("/api/client/servers/{$this->server->uuid}/power", ['command' => 'start'])
        ->assertForbidden();

    $this->actingAs($this->owner)
        ->deleteJson("{$this->endpoint}/{$grant->uuid}")
        ->assertNoContent();

    expect($this->server->subusers()->count())->toBe(0);
});

it('keeps sharing out of a sub-user\'s reach whatever they hold', function () {
    // Every permission in the catalog, and still not this one: a permission to grant permissions
    // would be a permission to grant every permission.
    $invitee = subuser($this->server, ServerPermission::cases());

    $this->actingAs($invitee)->getJson($this->endpoint)->assertForbidden();
    $this->actingAs($invitee)
        ->postJson($this->endpoint, ['email' => 'someone@example.test', 'permissions' => []])
        ->assertForbidden();
});

it('lets an operator who manages servers unpick a share', function () {
    $invitee = subuser($this->server, []);
    $grant = $this->server->subusers()->where('user_id', $invitee->id)->firstOrFail();

    $support = operator([AdminPermission::SERVERS_READ]);
    $this->actingAs($support)->getJson($this->endpoint)->assertForbidden();

    $manager = operator([AdminPermission::SERVERS_MANAGE]);
    $this->actingAs($manager)->deleteJson("{$this->endpoint}/{$grant->uuid}")->assertNoContent();
});

it('does not resolve a grant belonging to another server', function () {
    [, , , $other] = createServerModel();
    $elsewhere = subuser($other, []);
    $grant = $other->subusers()->where('user_id', $elsewhere->id)->firstOrFail();

    $this->actingAs($this->owner)
        ->deleteJson("{$this->endpoint}/{$grant->uuid}")
        ->assertNotFound();
});

it('drops every grant when a server changes hands', function () {
    subuser($this->server, [ServerPermission::POWER_START]);
    $newOwner = User::factory()->create();

    $this->actingAs(admin())
        ->patchJson("/api/admin/servers/{$this->server->id}", ['user_id' => $newOwner->id])
        ->assertSuccessful();

    expect($this->server->subusers()->count())->toBe(0);
});

it('drops every grant when a server is deleted', function () {
    $invitee = subuser($this->server, []);

    $this->server->delete();

    expect(User::query()->whereKey($invitee->id)->exists())->toBeTrue()
        ->and($invitee->serverShares()->count())->toBe(0);
});
