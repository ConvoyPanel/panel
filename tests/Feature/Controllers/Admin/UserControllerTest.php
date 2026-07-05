<?php

use App\Enums\Api\ApiKeyType;
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
