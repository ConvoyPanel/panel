<?php

use App\Models\AddressBlockGroup;
use App\Models\User;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
    $this->group = AddressBlockGroup::factory()->create();
});

function storeBlock(array $overrides = []): TestResponse
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

it('rejects a prefix longer than the address family allows', function () {
    // Previously accepted (both fields were capped at 128 regardless of version), then reached
    // `1 << (32 - 64)` in GenerateAddressesAction and 500'd on a negative bit shift.
    storeBlock(['version' => 'ipv4', 'prefix_length_to' => 64])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prefix_length_to']);
});

it('rejects an inverted prefix range', function () {
    storeBlock(['prefix_length_from' => 28, 'prefix_length_to' => 24])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prefix_length_to']);
});

it('rejects a single-unit block whose gateway consumes its only unit', function () {
    storeBlock([
        'gateway' => '192.0.2.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 24,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['gateway']);
});

it('accepts a single-unit block with no gateway', function () {
    storeBlock(['prefix_length_from' => 24, 'prefix_length_to' => 24])->assertSuccessful();
});

it('accepts a delegating block with a gateway', function () {
    storeBlock(['gateway' => '192.0.2.1', 'prefix_length_to' => 28])->assertSuccessful();
});

it('accepts an ipv6 block at full prefix length', function () {
    storeBlock([
        'version' => 'ipv6',
        'base_ip' => '2001:db8::',
        'gateway' => '2001:db8::1',
        'prefix_length_from' => 112,
        'prefix_length_to' => 128,
    ])->assertSuccessful();
});
