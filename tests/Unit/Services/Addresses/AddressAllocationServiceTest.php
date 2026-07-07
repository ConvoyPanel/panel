<?php

use App\Enums\Network\AddressVersion;
use App\Exceptions\Service\Address\InsufficientAddressesException;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\AddressBlockGroupToInterface;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Services\Addresses\AddressAllocationService;
use Illuminate\Support\Facades\DB;

/**
 * Build a network interface wired to one address block group holding a single block, then
 * pre-materialize $free unassigned rows (and optionally $assigned rows already taken by a server).
 *
 * @return array{interface: NetworkInterface, block: AddressBlock}
 */
function interfaceWithBlock(AddressVersion $version, int $free, int $assigned = 0): array
{
    $node = Node::factory()->for(Location::factory())->create();
    $group = AddressBlockGroup::factory()->create();
    $interface = NetworkInterface::create(['node_id' => $node->id, 'name' => 'vmbr0']);
    AddressBlockGroupToInterface::create([
        'address_block_group_id' => $group->id,
        'network_interface_id' => $interface->id,
    ]);

    $factory = AddressBlock::factory()->for($group);
    $block = ($version === AddressVersion::IPv6 ? $factory->ipv6() : $factory)->create();

    $octet = 1;
    for ($i = 0; $i < $free; $i++) {
        Address::factory()->for($block)->create([
            'ip' => addressAt($version, $octet++),
            'server_id' => null,
        ]);
    }

    if ($assigned > 0) {
        $server = Server::factory()->for($node)->create();
        for ($i = 0; $i < $assigned; $i++) {
            Address::factory()->for($block)->create([
                'ip' => addressAt($version, $octet++),
                'server_id' => $server->id,
            ]);
        }
    }

    return ['interface' => $interface, 'block' => $block];
}

function addressAt(AddressVersion $version, int $n): string
{
    return $version === AddressVersion::IPv6 ? sprintf('2001:db8::%x', $n) : "10.0.0.$n";
}

/** The allocator reserves rows with a row lock, so it must run inside a transaction (like its caller). */
function allocate(int $interfaceId, int $v4, int $v6)
{
    return DB::transaction(fn () => app(AddressAllocationService::class)->handle($interfaceId, $v4, $v6));
}

it('allocates the requested number of free IPv4 addresses from pre-materialized rows', function () {
    ['interface' => $interface] = interfaceWithBlock(AddressVersion::IPv4, free: 5);

    $allocated = allocate($interface->id, 3, 0);

    expect($allocated)->toHaveCount(3)
        ->and($allocated->every(fn (Address $a) => $a->server_id === null))->toBeTrue()
        // addressBlock is eager-loaded so ServerCreationService can read $address->version.
        ->and($allocated->every(fn (Address $a) => $a->relationLoaded('addressBlock')))->toBeTrue();
});

it('allocates a mix of IPv4 and IPv6 across the same interface', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $group = AddressBlockGroup::factory()->create();
    $interface = NetworkInterface::create(['node_id' => $node->id, 'name' => 'vmbr0']);
    AddressBlockGroupToInterface::create([
        'address_block_group_id' => $group->id,
        'network_interface_id' => $interface->id,
    ]);

    $v4 = AddressBlock::factory()->for($group)->create();
    $v6 = AddressBlock::factory()->for($group)->ipv6()->create();
    Address::factory()->count(4)->for($v4)->sequence(fn ($s) => ['ip' => "10.0.0.{$s->index}", 'server_id' => null])->create();
    Address::factory()->count(4)->for($v6)->sequence(fn ($s) => ['ip' => sprintf('2001:db8::%x', $s->index), 'server_id' => null])->create();

    $allocated = allocate($interface->id, 2, 3);

    expect($allocated)->toHaveCount(5);
    expect($allocated->filter(fn (Address $a) => $a->version === AddressVersion::IPv4))->toHaveCount(2);
    expect($allocated->filter(fn (Address $a) => $a->version === AddressVersion::IPv6))->toHaveCount(3);
});

it('skips addresses already assigned to a server', function () {
    ['interface' => $interface, 'block' => $block] = interfaceWithBlock(AddressVersion::IPv4, free: 2, assigned: 3);

    $allocated = allocate($interface->id, 2, 0);

    expect($allocated)->toHaveCount(2)
        ->and($allocated->every(fn (Address $a) => $a->server_id === null))->toBeTrue();

    // Only the two free rows were handed out; nothing new was created.
    expect(Address::where('address_block_id', $block->id)->count())->toBe(5);
});

it('throws when there are not enough free addresses', function () {
    ['interface' => $interface] = interfaceWithBlock(AddressVersion::IPv4, free: 2);

    expect(fn () => allocate($interface->id, 3, 0))
        ->toThrow(InsufficientAddressesException::class);
});

it('throws when the interface has no block of the requested version', function () {
    // IPv4-only interface asked for an IPv6 address.
    ['interface' => $interface] = interfaceWithBlock(AddressVersion::IPv4, free: 5);

    expect(fn () => allocate($interface->id, 0, 1))
        ->toThrow(InsufficientAddressesException::class);
});

it('hands out the numerically-lowest free IP first (inet ordering, not lexical)', function () {
    ['interface' => $interface, 'block' => $block] = interfaceWithBlock(AddressVersion::IPv4, free: 0);

    // Insert .10 before .2 so row id order is the reverse of numeric IP order. With a text column
    // ORDER BY ip would return '10.0.0.10' first ('1' < '2' lexically); with inet it returns '.2'.
    Address::factory()->for($block)->create(['ip' => '10.0.0.10', 'server_id' => null]);
    Address::factory()->for($block)->create(['ip' => '10.0.0.2', 'server_id' => null]);

    $allocated = allocate($interface->id, 1, 0);

    expect($allocated->first()->ip)->toBe('10.0.0.2');
});

it('never materializes new rows (pure consume)', function () {
    ['interface' => $interface, 'block' => $block] = interfaceWithBlock(AddressVersion::IPv4, free: 4);

    allocate($interface->id, 2, 0);

    expect(Address::where('address_block_id', $block->id)->count())->toBe(4);
});
