<?php

use App\Enums\Audit\AuditEvent;
use App\Enums\Network\AddressState;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;

it('records only the fields an address update changed', function () {
    $group = AddressBlockGroup::factory()->create();
    $block = AddressBlock::factory()->for($group)->create([
        'base_ip' => '192.0.2.0',
        'gateway' => null,
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);
    $server = Server::factory()->for(Node::factory()->for(Location::factory()))->for(Storage::factory())->create();
    $address = Address::factory()->for($block)->create([
        'ip' => '192.0.2.50',
        'server_id' => $server->id,
        'state' => AddressState::Assigned,
    ]);

    $this->travel(5)->seconds();

    // Unassigning frees the address as well, so the state moves with it.
    $this->actingAs(admin())
        ->patchJson("/api/admin/address-block-groups/{$group->id}/address-blocks/{$block->id}/addresses/{$address->id}", [
            'server_id' => null,
        ])
        ->assertOk();

    expect(AuditLog::query()->forEvent(AuditEvent::ADMIN_ADDRESS_UPDATED)->sole()->properties['changed'])
        ->toEqualCanonicalizing(['server_id', 'state']);
});
