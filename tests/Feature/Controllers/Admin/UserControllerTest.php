<?php

use App\Enums\Api\ApiKeyType;
use App\Models\SessionRecord;
use App\Models\User;

it('revokes API tokens when an admin is demoted', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create(['root_admin' => true]);
    $target->createToken('test', ApiKeyType::ACCOUNT);

    expect($target->tokens()->count())->toBe(1);

    $response = $this->actingAs($admin)->putJson("/api/admin/users/{$target->id}", [
        'name' => $target->name,
        'email' => $target->email,
        'root_admin' => false,
    ]);

    $response->assertOk();
    expect($target->fresh()->tokens()->count())->toBe(0);
});

it('keeps API tokens when an update does not demote the user', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create(['root_admin' => true]);
    $target->createToken('test', ApiKeyType::ACCOUNT);

    $this->actingAs($admin)->putJson("/api/admin/users/{$target->id}", [
        'name' => 'Renamed',
        'email' => $target->email,
        'root_admin' => true,
    ])->assertOk();

    expect($target->fresh()->tokens()->count())->toBe(1);
});

it('deletes users through the deletion service cleanup path', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create();
    $target->createToken('test', ApiKeyType::ACCOUNT);

    SessionRecord::query()->create([
        'session_id' => 'target-device',
        'user_id' => $target->id,
        'ip_address' => '203.0.113.7',
        'user_agent' => 'Mozilla/5.0',
        'last_active_at' => now(),
    ]);

    $handler = app('session')->getHandler();
    $handler->write('target-device', 'live');

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$target->id}")
        ->assertNoContent();

    expect(User::query()->whereKey($target->id)->exists())->toBeFalse()
        ->and($target->tokens()->count())->toBe(0)
        ->and(SessionRecord::query()->where('user_id', $target->id)->exists())->toBeFalse()
        ->and($handler->read('target-device'))->toBe('');
});

it('mints a single-use signed SSO link for a user', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create();

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/users/{$target->id}/generate-sso-token")
        ->assertSuccessful();

    $response->assertJsonPath('data.userId', $target->id);
    $link = $response->json('data.link');
    expect($link)->toContain('/api/auth/sso/'.$target->uuid)
        ->and($link)->toContain('signature=');

    // The freshly minted link is honoured by the consume endpoint (logs the target in). Drop the
    // admin session first so the `guest`-only consume route runs (a fresh browser has no session).
    auth()->logout();

    $this->get($link)->assertRedirect(route('index'));
    $this->assertAuthenticatedAs($target);
});
