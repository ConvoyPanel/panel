<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\AuditLog;

it('records only the fields an address block update changed', function () {
    $group = AddressBlockGroup::factory()->create();
    $block = AddressBlock::factory()->for($group)->create([
        'name' => 'Before',
        'base_ip' => '192.0.2.0',
        'gateway' => '192.0.2.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);

    $this->travel(5)->seconds();

    $this->actingAs(admin())
        ->putJson("/api/admin/address-block-groups/{$group->id}/address-blocks/{$block->id}", [
            'name' => 'After',
            'description' => $block->description,
            'base_ip' => '192.0.2.0',
            'gateway' => '192.0.2.1',
            'mac_address' => $block->mac_address,
            'prefix_length_from' => 24,
            'prefix_length_to' => 32,
        ])
        ->assertOk();

    expect(AuditLog::query()->forEvent(AuditEvent::ADMIN_ADDRESS_BLOCK_UPDATED)->sole()->properties['changed'])
        ->toBe(['name']);
});
