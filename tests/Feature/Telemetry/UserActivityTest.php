<?php

use Carbon\CarbonImmutable;
use Convoy\Enums\Api\ApiKeyType;
use Convoy\Models\User;
use Illuminate\Support\Facades\DB;

$lastActive = fn (User $user) => DB::table('users')->where('id', $user->id)->value('last_active_at');

it('stamps when an account last used the panel in a browser', function () use ($lastActive) {
    $this->travelTo(CarbonImmutable::parse('2026-10-23 14:37:00'));
    $user = User::factory()->create(['updated_at' => now()->subWeek()]);

    $this->actingAs($user)->getJson('/api/client/servers')->assertOk();

    expect($lastActive($user))->toBe('2026-10-23 14:37:00')
        // Activity isn't an edit to the account.
        ->and($user->fresh()->updated_at->toDateTimeString())->toBe(now()->subWeek()->toDateTimeString());
});

it('writes at most once a minute per account', function () use ($lastActive) {
    $this->travelTo(CarbonImmutable::parse('2026-10-23 14:37:00'));
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/client/servers')->assertOk();
    $this->travel(30)->seconds();
    $this->actingAs($user->fresh())->getJson('/api/client/servers')->assertOk();

    expect($lastActive($user))->toBe('2026-10-23 14:37:00');

    $this->travel(31)->seconds();
    $this->actingAs($user->fresh())->getJson('/api/client/servers')->assertOk();

    expect($lastActive($user))->toBe('2026-10-23 14:38:01');
});

it('does not count a script calling the API with a token', function () use ($lastActive) {
    $user = User::factory()->create(['root_admin' => true]);
    $token = $user->createToken('billing', ApiKeyType::APPLICATION)->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/application/locations')
        ->assertOk();

    expect($lastActive($user))->toBeNull();
});
