<?php

use Convoy\Data\Server\Deployments\CloudinitAddressConfigData;
use Convoy\Services\Servers\NetworkService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

// Helpers -----------------------------------------------------------------

/**
 * Build a fake Proxmox GET /config response with a net0 string.
 * The node factory defaults to network='vmbr0', so use that in fixtures.
 */
function fakeConfigWithNet0(string $net0): array
{
    return ['data' => ['net0' => $net0]];
}

function fakeConfigWithoutNet0(): array
{
    return ['data' => ['cores' => 2, 'memory' => 2048]];
}

// updateRateLimit ---------------------------------------------------------

it('skips net0 update when rate limit already matches', function () {
    // Proxmox already has rate=100; we call updateRateLimit(100) — no write expected.
    $net0 = 'virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0,firewall=1,rate=100';

    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(fakeConfigWithNet0($net0), 200)  // GET via cloudinit repo (MAC lookup)
            ->push(fakeConfigWithNet0($net0), 200), // GET via allocation repo (config)
    ]);

    [,,,  $server] = createServerModel();

    app(NetworkService::class)->updateRateLimit($server, 100);

    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
});

it('normalises MAC case when comparing net0 for rate limit no-op', function () {
    // Proxmox returns uppercase MAC; eloquent/proxmox MAC is lowercase — should still be a no-op.
    $net0 = 'virtio=aa:bb:cc:dd:ee:ff,bridge=vmbr0,firewall=1,rate=100';
    $net0Uppercase = 'virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0,firewall=1,rate=100';

    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(fakeConfigWithNet0($net0Uppercase), 200)
            ->push(fakeConfigWithNet0($net0), 200),
    ]);

    [,,,  $server] = createServerModel();

    app(NetworkService::class)->updateRateLimit($server, 100);

    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
});

it('sends net0 update when rate limit differs', function () {
    $net0 = 'virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0,firewall=1,rate=50';

    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(fakeConfigWithNet0($net0), 200)
            ->push(fakeConfigWithNet0($net0), 200)
            ->push(['data' => 'UPID:proxmox:000:dummy'], 200), // POST response
    ]);

    [,,,  $server] = createServerModel();

    app(NetworkService::class)->updateRateLimit($server, 100);

    Http::assertSent(function (Request $r) {
        return $r->method() === 'POST'
            && str_contains($r->url(), '/config')
            && str_contains($r->body(), 'rate')
            && str_contains($r->body(), '100');
    });
});

it('removes rate key when updateRateLimit is called with null', function () {
    $net0 = 'virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0,firewall=1,rate=100';

    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(fakeConfigWithNet0($net0), 200)
            ->push(fakeConfigWithNet0($net0), 200)
            ->push(['data' => 'UPID:proxmox:000:dummy'], 200),
    ]);

    [,,,  $server] = createServerModel();

    app(NetworkService::class)->updateRateLimit($server, null);

    Http::assertSent(function (Request $r) {
        return $r->method() === 'POST'
            && !str_contains($r->body(), 'rate');
    });
});

it('returns early from updateRateLimit when net0 key is absent', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(fakeConfigWithoutNet0(), 200)
            ->push(fakeConfigWithoutNet0(), 200),
    ]);

    [,,,  $server] = createServerModel();

    app(NetworkService::class)->updateRateLimit($server, 100);

    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
});
