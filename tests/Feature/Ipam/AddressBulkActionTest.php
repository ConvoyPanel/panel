<?php

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
    $this->group = AddressBlockGroup::factory()->create();
    $this->block = AddressBlock::factory()->for($this->group)->create([
        'base_ip' => '192.0.2.0',
        'gateway' => null,
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);
});

function bulkUrl(AddressBlock $block): string
{
    return "/api/admin/address-block-groups/{$block->address_block_group_id}"
        ."/address-blocks/{$block->id}/addresses/bulk";
}

it('reserves a run of addresses in one request', function () {
    $addresses = collect(range(2, 10))->map(fn (int $host) => Address::factory()
        ->for($this->block)
        ->create(['ip' => "192.0.2.{$host}", 'state' => AddressState::Available]));

    $result = $this->actingAs($this->admin)
        ->postJson(bulkUrl($this->block), [
            'action' => 'reserve',
            'ids' => $addresses->pluck('id')->all(),
        ])
        ->assertSuccessful()
        ->json('data');

    expect($result['affected'])->toBe(9)
        ->and($result['skipped'])->toBe(0);

    $addresses->each(function (Address $address) {
        expect($address->refresh()->state)->toBe(AddressState::Reserved)
            ->and($address->state_reason)->toBe(AddressStateReason::Admin);
    });
});

it('skips what an action cannot touch instead of failing the batch', function () {
    $server = Server::factory()
        ->for(Node::factory()->for(Location::factory()))
        ->for(Storage::factory())
        ->create();

    $available = Address::factory()->for($this->block)->create(['ip' => '192.0.2.20']);
    $assigned = Address::factory()->for($this->block)->create([
        'ip' => '192.0.2.21',
        'server_id' => $server->id,
    ]);
    $system = Address::factory()->for($this->block)->systemReserved()->create(['ip' => '192.0.2.0']);

    $result = $this->actingAs($this->admin)
        ->postJson(bulkUrl($this->block), [
            'action' => 'reserve',
            'ids' => [$available->id, $assigned->id, $system->id],
        ])
        ->assertSuccessful()
        ->json('data');

    expect($result['affected'])->toBe(1)
        ->and($result['skipped'])->toBe(2)
        ->and($available->refresh()->state)->toBe(AddressState::Reserved)
        ->and($assigned->refresh()->state)->toBe(AddressState::Assigned)
        ->and($system->refresh()->state_reason)->toBe(AddressStateReason::System);
});

it('never releases a system reservation', function () {
    $system = Address::factory()->for($this->block)->systemReserved()->create(['ip' => '192.0.2.0']);
    $held = Address::factory()->for($this->block)->create([
        'ip' => '192.0.2.30',
        'state' => AddressState::Reserved,
    ]);

    $result = $this->actingAs($this->admin)
        ->postJson(bulkUrl($this->block), [
            'action' => 'release',
            'ids' => [$system->id, $held->id],
        ])
        ->assertSuccessful()
        ->json('data');

    expect($result['affected'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and($held->refresh()->state)->toBe(AddressState::Available)
        ->and($system->refresh()->state)->toBe(AddressState::Reserved);
});

it('leaves an assigned address alone when deleting a selection', function () {
    $server = Server::factory()
        ->for(Node::factory()->for(Location::factory()))
        ->for(Storage::factory())
        ->create();

    $free = Address::factory()->for($this->block)->create(['ip' => '192.0.2.40']);
    $assigned = Address::factory()->for($this->block)->create([
        'ip' => '192.0.2.41',
        'server_id' => $server->id,
    ]);

    $result = $this->actingAs($this->admin)
        ->postJson(bulkUrl($this->block), [
            'action' => 'delete',
            'ids' => [$free->id, $assigned->id],
        ])
        ->assertSuccessful()
        ->json('data');

    expect($result['affected'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and(Address::find($free->id))->toBeNull()
        ->and(Address::find($assigned->id))->not->toBeNull();
});

it('will not touch an address from another block named in the body', function () {
    $otherBlock = AddressBlock::factory()->for($this->group)->create([
        'base_ip' => '198.51.100.0',
        'gateway' => null,
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);
    $outsider = Address::factory()->for($otherBlock)->create(['ip' => '198.51.100.5']);

    $result = $this->actingAs($this->admin)
        ->postJson(bulkUrl($this->block), [
            'action' => 'reserve',
            'ids' => [$outsider->id],
        ])
        ->assertSuccessful()
        ->json('data');

    expect($result['affected'])->toBe(0)
        ->and($outsider->refresh()->state)->toBe(AddressState::Available);
});

it('rejects an action it does not know', function () {
    $address = Address::factory()->for($this->block)->create(['ip' => '192.0.2.50']);

    $this->actingAs($this->admin)
        ->postJson(bulkUrl($this->block), ['action' => 'vaporise', 'ids' => [$address->id]])
        ->assertStatus(422);
});
