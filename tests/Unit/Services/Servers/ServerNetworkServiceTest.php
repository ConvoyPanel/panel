<?php

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
