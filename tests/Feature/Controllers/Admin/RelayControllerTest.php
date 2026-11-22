<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\Relay;

it('records only the fields a relay update changed', function () {
    $relay = Relay::factory()->create(['name' => 'Before']);

    $this->travel(5)->seconds();

    $this->actingAs(admin())
        ->putJson("/api/admin/relays/{$relay->id}", [
            'name' => 'After',
            'public_url' => $relay->public_url,
            'panel_url_override' => $relay->panel_url_override,
        ])
        ->assertOk();

    expect(AuditLog::query()->forEvent(AuditEvent::ADMIN_RELAY_UPDATED)->sole()->properties['changed'])
        ->toBe(['name']);
});
