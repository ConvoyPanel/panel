<?php

use App\Enums\Network\AddressVersion;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/**
 * A block's version is no longer stored: base_ip is the only thing that says which family a block
 * belongs to, and the column is generated from it. These cover the two halves of that — the value
 * always tracks base_ip, and a caller cannot set it to something base_ip contradicts.
 */
beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
    $this->group = AddressBlockGroup::factory()->create();
});

function storeVersionedBlock(array $overrides = []): TestResponse
{
    return test()->actingAs(test()->admin)->postJson(
        '/api/admin/address-block-groups/'.test()->group->id.'/address-blocks',
        array_merge([
            'name' => 'block',
            'description' => null,
            'version' => 'ipv4',
            'base_ip' => '192.0.2.0',
            'gateway' => null,
            'mac_address' => null,
            'prefix_length_from' => 24,
            'prefix_length_to' => 32,
        ], $overrides),
    );
}

it('reads the version off base_ip', function () {
    $v4 = AddressBlock::factory()->create(['base_ip' => '192.0.2.0']);
    $v6 = AddressBlock::factory()->ipv6()->create(['base_ip' => '2001:db8::']);

    expect($v4->version)->toBe(AddressVersion::IPv4)
        ->and($v6->version)->toBe(AddressVersion::IPv6);

    // The generated column agrees, so `where version = ...` filters still select the same rows.
    expect(AddressBlock::where('version', 'ipv6')->pluck('id')->all())->toBe([$v6->id]);
});

it('answers for an unsaved block, which the geometry validator builds', function () {
    $block = new AddressBlock(['base_ip' => '2001:db8::', 'prefix_length_from' => 48, 'prefix_length_to' => 128]);

    expect($block->version)->toBe(AddressVersion::IPv6)
        ->and($block->maxPrefixLength())->toBe(128);
});

it('drops a mass-assigned version rather than writing to the generated column', function () {
    // The store endpoint validates `version` and hands the whole validated payload to create(),
    // so the guard is what keeps that from reaching Postgres as an insert into a generated column.
    $block = $this->group->addressBlocks()->create([
        'version' => 'ipv6',
        'base_ip' => '192.0.2.0',
        'gateway' => null,
        'mac_address' => null,
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);

    expect($block->version)->toBe(AddressVersion::IPv4)
        ->and($block->fresh()->version)->toBe(AddressVersion::IPv4);
});

it('tracks base_ip when it changes underneath the row', function () {
    $block = AddressBlock::factory()->create(['base_ip' => '192.0.2.0']);

    DB::table('address_blocks')->where('id', $block->id)->update(['base_ip' => '2001:db8::']);

    expect($block->fresh()->version)->toBe(AddressVersion::IPv6);
});

it('rejects a base ip of a different family than the declared version', function () {
    storeVersionedBlock(['version' => 'ipv6'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['base_ip']);
});

it('stores the version derived from the submitted base ip', function () {
    $response = storeVersionedBlock([
        'version' => 'ipv6',
        'base_ip' => '2001:db8::',
        'prefix_length_from' => 112,
        'prefix_length_to' => 128,
    ])->assertSuccessful();

    expect($response->json('data.version'))->toBe('ipv6');
});
