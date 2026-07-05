<?php

use App\Data\Server\Proxmox\Config\CloudinitConfigData;
use App\Data\Server\Proxmox\Config\IpConfigData;
use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Services\Servers\CloudinitService;
use Illuminate\Support\Facades\Http;

/** An in-memory IPv4 Address (gateway resolves off its block; no DB needed). */
function fakeIpv4(string $ip, int $prefixLength, string $gateway): Address
{
    $block = new AddressBlock;
    $block->gateway = $gateway;

    $address = new Address;
    $address->ip = $ip;
    $address->prefix_length = $prefixLength;
    $address->setRelation('addressBlock', $block);

    return $address;
}

it('parses a PVE ipconfig string into structured fields, order-insensitively', function () {
    $parsed = IpConfigData::fromString('gw=127.0.0.1,ip=1.1.1.2/24');

    expect($parsed->ip)->toBe('1.1.1.2/24')
        ->and($parsed->gateway)->toBe('127.0.0.1')
        ->and($parsed->ip6)->toBeNull()
        ->and($parsed->gateway6)->toBeNull()
        // key order doesn't change the parsed structure — the basis for no-op detection
        ->and($parsed->toArray())->toBe(IpConfigData::fromString('ip=1.1.1.2/24,gw=127.0.0.1')->toArray());
});

it('models per-NIC ipconfig off the raw config, keyed by NIC index', function () {
    $cloudinit = CloudinitConfigData::fromRaw(serverConfigFixture()['data']);

    expect($cloudinit->ipConfigs->get(0)?->ip)->toBe('1.1.1.2/24')
        ->and($cloudinit->ipConfigs->get(0)?->gateway)->toBe('127.0.0.1');
});

it('skips the hostname write when name and searchdomain already match', function () {
    // Fixture name and searchdomain are both "windows.com".
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfigFixture(), 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    app(CloudinitService::class)->setHostname($server, 'windows.com');

    Http::assertNotSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), '/config'));
});

it('writes name and searchdomain when the hostname changed', function () {
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfigFixture(), 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    app(CloudinitService::class)->setHostname($server, 'example.com');

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), '/config')
        && ($request['name'] ?? null) === 'example.com'
        && ($request['searchdomain'] ?? null) === 'example.com');
});

it('skips ipconfig writes for NICs already at the desired address', function () {
    // Fixture ipconfig0 is exactly "ip=1.1.1.2/24,gw=127.0.0.1".
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfigFixture(), 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    app(CloudinitService::class)->setIpConfig($server, fakeIpv4('1.1.1.2', 24, '127.0.0.1'), null);

    Http::assertNotSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), '/config'));
});

it('writes ipconfig for a NIC whose address changed', function () {
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfigFixture(), 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    app(CloudinitService::class)->setIpConfig($server, fakeIpv4('9.9.9.9', 24, '127.0.0.1'), null);

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), '/config')
        && ($request['ipconfig0'] ?? null) === 'ip=9.9.9.9/24,gw=127.0.0.1');
});

it('threads the config digest through setIpConfig and surfaces a mismatch as a 409', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            // getConfig() — carries the digest we echo back on the write
            ->push(serverConfigFixture(), 200)
            // update() — Proxmox rejects the write because the config changed
            ->push(
                ['data' => null, 'errors' => ['detected modified configuration - file changed by other user? Try again.']],
                500,
            ),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    expect(fn () => app(CloudinitService::class)->setIpConfig($server, null, null))
        ->toThrow(ConfigModifiedException::class);
});
