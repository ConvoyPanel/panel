<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Settings\AccountSettings;

beforeEach(function () {
    $this->user = User::factory()->create(['root_admin' => true]);
});

it('starts permissive on every switch', function () {
    // The self-service screen shipped before this policy did, so an install that
    // upgrades into it must find nothing taken away.
    $this->actingAs($this->user)
        ->getJson('/api/admin/settings/account')
        ->assertOk()
        ->assertJsonPath('data.allowNameChange', true)
        ->assertJsonPath('data.allowEmailChange', true)
        ->assertJsonPath('data.allowPasswordChange', true)
        ->assertJsonPath('data.allowAvatarChange', true);
});

it('stores the policy it was given', function () {
    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/account', [
            'allow_name_change' => false,
            'allow_email_change' => false,
            'allow_password_change' => true,
            'allow_avatar_change' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.allowNameChange', false)
        ->assertJsonPath('data.allowEmailChange', false)
        ->assertJsonPath('data.allowPasswordChange', true)
        ->assertJsonPath('data.allowAvatarChange', false);

    $settings = app(AccountSettings::class)->refresh();

    expect($settings->allow_name_change)->toBeFalse()
        ->and($settings->allow_email_change)->toBeFalse()
        ->and($settings->allow_password_change)->toBeTrue()
        ->and($settings->allow_avatar_change)->toBeFalse();
});

it('records what the policy was changed to', function () {
    $this->actingAs($this->user)->putJson('/api/admin/settings/account', [
        'allow_name_change' => false,
        'allow_email_change' => true,
        'allow_password_change' => true,
        'allow_avatar_change' => true,
    ]);

    $entry = AuditLog::query()
        ->where('event', '=', AuditEvent::ADMIN_SETTINGS_ACCOUNT_UPDATED)
        ->sole();

    expect($entry->properties['allow_name_change'])->toBeFalse()
        ->and($entry->subject_id)->toBeNull();
});

it('refuses a partial policy', function () {
    // One form over the whole policy: a body missing a switch is a broken
    // caller, not a request to leave that one alone.
    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/account', ['allow_name_change' => false])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'allow_email_change',
            'allow_password_change',
            'allow_avatar_change',
        ]);
});

it('is closed to a non-admin', function () {
    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->getJson('/api/admin/settings/account')
        ->assertForbidden();
});
