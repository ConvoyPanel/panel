<?php

use App\Models\NetworkInterface;
use App\Services\Servers\ServerNetworkService;
use Illuminate\Support\Facades\Http;

it('does not rewrite NICs that are already in the desired state', function () {
    // A config with one already-firewalled NIC (net0) and one that still needs
    // it (net1). The server has no primary address, so mac/bridge are left
    // alone and the firewall flag is the only thing that can differ.
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfigFixture([
            'net0' => 'virtio=66:87:C3:0D:86:13,bridge=vmbr1,firewall=1',
            'net1' => 'virtio=66:87:C3:0D:86:14,bridge=vmbr1',
        ]), 200),
        '*/firewall/ipset*' => Http::response(['data' => []], 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    app(ServerNetworkService::class)->syncSettings($server);

    // The network-device write must include net1 (needed the firewall flag) but
    // never net0 (already firewalled).
    Http::assertSent(fn ($request) => str_contains($request->url(), '/qemu/')
        && str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && isset($request['net1'])
        && ! isset($request['net0']));

    // And no config write should ever carry net0 as a key.
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && isset($request['net0']));
});

it('writes the selected interface default VLAN tag to NICs', function () {
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfigFixture([
            'net0' => 'virtio=66:87:C3:0D:86:13,bridge=vmbr0,firewall=1',
        ]), 200),
        '*/firewall/ipset*' => Http::response(['data' => []], 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , $node, $server] = createServerModel();
    $interface = NetworkInterface::create([
        'node_id' => $node->id,
        'name' => 'vmbr1',
        'is_vlan_aware' => true,
        'vlan_tag' => 42,
    ]);
    $server->update(['network_interface_id' => $interface->id]);

    app(ServerNetworkService::class)->syncSettings($server);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/qemu/')
        && str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && isset($request['net0'])
        && str_contains($request['net0'], 'bridge=vmbr1')
        && str_contains($request['net0'], 'tag=42'));
});

it('uses a server VLAN override before the interface default', function () {
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfigFixture([
            'net0' => 'virtio=66:87:C3:0D:86:13,bridge=vmbr1,firewall=1,tag=42',
        ]), 200),
        '*/firewall/ipset*' => Http::response(['data' => []], 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , $node, $server] = createServerModel();
    $interface = NetworkInterface::create([
        'node_id' => $node->id,
        'name' => 'vmbr1',
        'is_vlan_aware' => true,
        'vlan_tag' => 42,
    ]);
    $server->update([
        'network_interface_id' => $interface->id,
        'vlan_tag' => 123,
    ]);

    app(ServerNetworkService::class)->syncSettings($server);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/qemu/')
        && str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && isset($request['net0'])
        && str_contains($request['net0'], 'tag=123')
        && ! str_contains($request['net0'], 'tag=42'));
});

it('clears a NIC VLAN tag when the selected interface has no desired tag', function () {
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfigFixture([
            'net0' => 'virtio=66:87:C3:0D:86:13,bridge=vmbr1,firewall=1,tag=42',
        ]), 200),
        '*/firewall/ipset*' => Http::response(['data' => []], 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , $node, $server] = createServerModel();
    $interface = NetworkInterface::create([
        'node_id' => $node->id,
        'name' => 'vmbr1',
        'is_vlan_aware' => true,
        'vlan_tag' => null,
    ]);
    $server->update(['network_interface_id' => $interface->id]);

    app(ServerNetworkService::class)->syncSettings($server);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/qemu/')
        && str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && isset($request['net0'])
        && ! str_contains($request['net0'], 'tag='));
});

it('preserves existing NIC VLAN tags when no network interface is selected or inferred', function () {
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfigFixture([
            'net0' => 'virtio=66:87:C3:0D:86:13,bridge=vmbr1,firewall=0,tag=42',
        ]), 200),
        '*/firewall/ipset*' => Http::response(['data' => []], 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    app(ServerNetworkService::class)->syncSettings($server);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/qemu/')
        && str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && isset($request['net0'])
        && str_contains($request['net0'], 'tag=42'));
});
