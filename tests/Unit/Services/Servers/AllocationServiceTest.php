<?php

use Convoy\Services\Servers\AllocationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

// updateHardware ----------------------------------------------------------

it('skips hardware update when cores and memory already match', function () {
    // Server factory: cpu=2, memory=2048 MiB (2048 * 1024 * 1024 bytes).
    // Proxmox already reflects those values — no write expected.
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['cores' => 2, 'memory' => 2048]], 200),
    ]);

    [,,,  $server] = createServerModel();

    app(AllocationService::class)->updateHardware($server, $server->cpu, $server->memory);

    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
});

it('sends only changed fields when cores differ', function () {
    // Proxmox has cores=1 but we want 2; memory already matches.
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['cores' => 1, 'memory' => 2048]], 200)
            ->push(['data' => 'UPID:proxmox:000:dummy'], 200),
    ]);

    [,,,  $server] = createServerModel();

    app(AllocationService::class)->updateHardware($server, 2, $server->memory);

    Http::assertSent(function (Request $r) {
        $data = $r->data();
        return $r->method() === 'POST'
            && isset($data['cores'])
            && !isset($data['memory']);
    });
});

it('sends only changed fields when memory differs', function () {
    // Proxmox has correct cores but stale memory.
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['cores' => 2, 'memory' => 1024]], 200)
            ->push(['data' => 'UPID:proxmox:000:dummy'], 200),
    ]);

    [,,,  $server] = createServerModel();

    app(AllocationService::class)->updateHardware($server, $server->cpu, $server->memory);

    Http::assertSent(function (Request $r) {
        $data = $r->data();
        return $r->method() === 'POST'
            && isset($data['memory'])
            && !isset($data['cores']);
    });
});

it('sends both fields when both cores and memory differ', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(['data' => ['cores' => 1, 'memory' => 512]], 200)
            ->push(['data' => 'UPID:proxmox:000:dummy'], 200),
    ]);

    [,,,  $server] = createServerModel();

    app(AllocationService::class)->updateHardware($server, $server->cpu, $server->memory);

    Http::assertSent(function (Request $r) {
        $data = $r->data();
        return $r->method() === 'POST'
            && isset($data['cores'])
            && isset($data['memory']);
    });
});
