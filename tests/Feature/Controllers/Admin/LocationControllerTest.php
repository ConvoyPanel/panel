<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\Location;
use App\Models\User;

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

    $response->assertCreated();
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

it('records only the fields a location update changed', function () {
    $location = Location::factory()->create(['description' => 'Before']);

    $this->travel(5)->seconds();

    $this->actingAs(admin())->putJson("/api/admin/locations/{$location->id}", [
        'short_code' => $location->short_code,
        'description' => 'After',
    ])->assertOk();

    expect(AuditLog::query()->forEvent(AuditEvent::ADMIN_LOCATION_UPDATED)->sole()->properties['changed'])
        ->toBe(['description']);
});
