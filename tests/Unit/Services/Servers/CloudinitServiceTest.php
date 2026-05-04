<?php

use Convoy\Data\Server\Deployments\CloudinitAddressConfigData;
use Convoy\Services\Servers\CloudinitService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

// updateHostname ----------------------------------------------------------

it('skips hostname update when name and searchdomain already match', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['name' => 'vm.example.com', 'searchdomain' => 'vm.example.com']], 200),
    ]);

    [,,,  $server] = createServerModel();

    app(CloudinitService::class)->updateHostname($server, 'vm.example.com');

    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
});

it('sends hostname update when name differs', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['name' => 'old.example.com', 'searchdomain' => 'new.example.com']], 200)
            ->push(['data' => 'UPID:proxmox:000:dummy'], 200),
    ]);

    [,,,  $server] = createServerModel();

    app(CloudinitService::class)->updateHostname($server, 'new.example.com');

    Http::assertSent(function (Request $r) {
        $data = $r->data();
        return $r->method() === 'POST'
            && ($data['name'] ?? null) === 'new.example.com';
    });
});

it('sends only changed fields when only searchdomain differs', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['name' => 'new.example.com', 'searchdomain' => 'old.example.com']], 200)
            ->push(['data' => 'UPID:proxmox:000:dummy'], 200),
    ]);

    [,,,  $server] = createServerModel();

    app(CloudinitService::class)->updateHostname($server, 'new.example.com');

    Http::assertSent(function (Request $r) {
        $data = $r->data();
        return $r->method() === 'POST'
            && isset($data['searchdomain'])
            && !isset($data['name']);
    });
});

// updateIpConfig ----------------------------------------------------------

function ipv4Address(string $ip = '192.168.1.10', int $cidr = 24, string $gw = '192.168.1.1'): array
{
    return [
        'id' => 1,
        'address_pool_id' => 1,
        'server_id' => null,
        'type' => 'ipv4',
        'address' => $ip,
        'cidr' => $cidr,
        'gateway' => $gw,
        'mac_address' => null,
    ];
}

it('skips ipconfig update when desired config already matches current', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['ipconfig0' => 'ip=192.168.1.10/24,gw=192.168.1.1']], 200),
    ]);

    [,,,  $server] = createServerModel();

    $addresses = CloudinitAddressConfigData::from(['ipv4' => ipv4Address(), 'ipv6' => null]);

    app(CloudinitService::class)->updateIpConfig($server, $addresses);

    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
});

it('sends ipconfig update when IP address differs', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['ipconfig0' => 'ip=10.0.0.1/24,gw=10.0.0.254']], 200)
            ->push(['data' => 'UPID:proxmox:000:dummy'], 200),
    ]);

    [,,,  $server] = createServerModel();

    $addresses = CloudinitAddressConfigData::from(['ipv4' => ipv4Address(), 'ipv6' => null]);

    app(CloudinitService::class)->updateIpConfig($server, $addresses);

    Http::assertSent(function (Request $r) {
        return $r->method() === 'POST'
            && str_contains($r->body(), 'ipconfig0');
    });
});

it('deletes ipconfig0 when no addresses are provided but one exists in Proxmox', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['ipconfig0' => 'ip=192.168.1.10/24,gw=192.168.1.1']], 200)
            ->push(['data' => 'UPID:proxmox:000:dummy'], 200),
    ]);

    [,,,  $server] = createServerModel();

    $addresses = CloudinitAddressConfigData::from(['ipv4' => null, 'ipv6' => null]);

    app(CloudinitService::class)->updateIpConfig($server, $addresses);

    Http::assertSent(function (Request $r) {
        $data = $r->data();
        return $r->method() === 'POST'
            && ($data['delete'] ?? null) === 'ipconfig0';
    });
});

it('skips ipconfig update when both desired and current are empty', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => []], 200), // no ipconfig0 key in Proxmox
    ]);

    [,,,  $server] = createServerModel();

    $addresses = CloudinitAddressConfigData::from(['ipv4' => null, 'ipv6' => null]);

    app(CloudinitService::class)->updateIpConfig($server, $addresses);

    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
});
