<?php

use Convoy\Models\Location;
use Convoy\Models\User;

it('can fetch locations', function () {
    $user = User::factory()->create([
        'root_admin' => true,
    ]);

    $response = $this->actingAs($user)->getJson('/api/admin/locations');

    $response->assertOk();
});

it('can create a location', function () {
    $user = User::factory()->create([
        'root_admin' => true,
    ]);

    $response = $this->actingAs($user)->postJson('/api/admin/locations', [
        'name' => 'Test Location',
        'short_code' => 'test',
        'description' => 'This is a test location.',
    ]);

    $response->assertOk();
});

it('can update a location', function () {
    $user = User::factory()->create([
        'root_admin' => true,
    ]);

    $location = Location::factory()->create();

    $response = $this->actingAs($user)->putJson("/api/admin/locations/{$location->id}", [
        'name' => 'Test Location',
        'short_code' => 'test',
        'description' => 'This is a test location.',
    ]);

    $response->assertOk();
});

it('can delete a location', function () {
    $user = User::factory()->create([
        'root_admin' => true,
    ]);

    $location = Location::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/admin/locations/{$location->id}");

    $response->assertNoContent();
});
