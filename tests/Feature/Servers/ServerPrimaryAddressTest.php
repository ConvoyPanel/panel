<?php

use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\AddressBlockGroupToInterface;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Storage;
use App\Models\User;
use App\Services\Servers\ServerCreationService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->node = Node::factory()->for(Location::factory())->create();
    $this->storage = Storage::factory()->create();
    $this->node->storages()->attach($this->storage);

    $this->interface = NetworkInterface::create(['node_id' => $this->node->id, 'name' => 'vmbr0']);
    $group = AddressBlockGroup::factory()->create();
    AddressBlockGroupToInterface::create([
        'address_block_group_id' => $group->id,
        'network_interface_id' => $this->interface->id,
    ]);

    $v4 = AddressBlock::factory()->for($group)->create(['base_ip' => '192.0.2.0']);
    $v6 = AddressBlock::factory()->for($group)->ipv6()->create(['base_ip' => '2001:db8::']);
    Address::factory()->for($v4)->create(['ip' => '192.0.2.10', 'server_id' => null]);
    Address::factory()->for($v6)->create(['ip' => '2001:db8::10', 'server_id' => null]);
});

it('records the allocated addresses as the server primaries', function () {
    // Regression: these were picked with firstWhere('version', 'IPv4') — a string compared against
    // an AddressVersion enum, which never matches, so every server was created with no primaries.
    $server = app(ServerCreationService::class)->handle([
        'user_id' => $this->user->id,
        'node_id' => $this->node->id,
        'storage_id' => $this->storage->id,
        'vmid' => 12345,
        'hostname' => 'test-host',
        'name' => 'Test Server',
        'description' => null,
        'deferred_os_selection' => true,
        'should_create_vm' => false,
        'start_on_completion' => false,
        'account_password' => null,
        'template_uuid' => null,
        'limits' => [
            'cpu' => 2,
            'memory' => 2 * 1024 * 1024 * 1024,
            'disk' => 20 * 1024 * 1024 * 1024,
            'bandwidth' => -1,
            'network_interface_id' => $this->interface->id,
            'addresses_ipv4_count' => 1,
            'addresses_ipv6_count' => 1,
            'backups' => ['count' => -1, 'size' => -1],
        ],
    ]);

    expect($server->primaryIPv4Address?->ip)->toBe('192.0.2.10')
        ->and($server->primaryIPv6Address?->ip)->toBe('2001:db8::10');
});
