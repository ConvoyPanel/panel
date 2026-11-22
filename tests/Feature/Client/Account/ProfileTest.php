<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;

it('updates the display name', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->patchJson('/api/client/account/profile', ['name' => 'New Name'])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'New Name');

    expect($user->refresh()->name)->toBe('New Name');
});

it('rejects an empty display name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/api/client/account/profile', ['name' => ''])
        ->assertJsonValidationErrors('name');
});

it('records what a profile update changed', function () {
    $user = User::factory()->create(['name' => 'Before']);

    // A later second than the row's updated_at, as every real edit is, so the
    // save moves updated_at too and the entry must still list only the name.
    $this->travel(5)->seconds();

    $this->actingAs($user)->patchJson('/api/client/account/profile', ['name' => 'After']);

    $entry = AuditLog::query()->where('event', '=', AuditEvent::ACCOUNT_PROFILE_UPDATED)->sole();

    expect($entry->properties['changed'])->toBe(['name']);
});

it('records what an email change changed', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->travel(5)->seconds();

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->patchJson('/api/client/account/email', ['email' => 'new@example.com'])
        ->assertSuccessful();

    $entry = AuditLog::query()->where('event', '=', AuditEvent::ACCOUNT_PROFILE_UPDATED)->sole();

    expect($entry->properties['changed'])->toBe(['email']);
});

it('does not record an update that changed nothing', function () {
    $user = User::factory()->create(['name' => 'Same']);

    $this->actingAs($user)->patchJson('/api/client/account/profile', ['name' => 'Same']);

    expect(AuditLog::query()->where('event', '=', AuditEvent::ACCOUNT_PROFILE_UPDATED)->count())->toBe(0);
});

it('changes the email once identity is confirmed', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->patchJson('/api/client/account/email', ['email' => 'new@example.com'])
        ->assertSuccessful()
        ->assertJsonPath('data.email', 'new@example.com');

    expect($user->refresh()->email)->toBe('new@example.com');
});

it('refuses an email change on an unconfirmed session', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->patchJson('/api/client/account/email', ['email' => 'new@example.com'])
        ->assertForbidden();

    expect($user->refresh()->email)->toBe('old@example.com');
});

it('refuses an email another account already uses', function () {
    $user = User::factory()->create();
    $other = User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->patchJson('/api/client/account/email', ['email' => $other->email])
        ->assertJsonValidationErrors('email');
});

it('lets an account keep its own email', function () {
    $user = User::factory()->create(['email' => 'mine@example.com']);

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->patchJson('/api/client/account/email', ['email' => 'mine@example.com'])
        ->assertSuccessful();
});
