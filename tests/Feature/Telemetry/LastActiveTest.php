<?php

use Convoy\Models\User;

it('records when a signed-in user was last active, at most every few minutes', function () {
    $this->freezeSecond();
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/client/servers')->assertOk();
    $first = $user->fresh()->last_active_at;

    expect($first?->toDateTimeString())->toBe(now()->toDateTimeString());

    $this->travel(2)->minutes();
    $this->actingAs($user->fresh())->getJson('/api/client/servers')->assertOk();

    expect($user->fresh()->last_active_at->toDateTimeString())->toBe($first->toDateTimeString());

    $this->travel(4)->minutes();
    $this->actingAs($user->fresh())->getJson('/api/client/servers')->assertOk();

    expect($user->fresh()->last_active_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('leaves updated_at alone', function () {
    $user = User::factory()->create(['updated_at' => now()->subDay()]);

    $this->actingAs($user)->getJson('/api/client/servers')->assertOk();

    expect($user->fresh()->updated_at->toDateTimeString())->toBe($user->updated_at->toDateTimeString());
});

it('does not count requests nobody is signed in for', function () {
    $this->getJson('/api/client/servers')->assertUnauthorized();

    expect(User::query()->where('last_active_at', '>=', now()->subMinute())->exists())->toBeFalse();
});
