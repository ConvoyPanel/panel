<?php

use App\Services\Servers\DisplayConsoleService;
use Illuminate\Support\Facades\Http;

/** Fake PVE's `/pending` rows, which is what the display is read from. */
function fakePending(array $rows): void
{
    fakeProxmox(['*/pending' => Http::response(['data' => $rows], 200)]);
}

it('reports a display for a VM with no vga key, which is PVE default', function () {
    fakePending([['key' => 'cores', 'value' => 2]]);
    [, , , $server] = createServerModel();

    $status = app(DisplayConsoleService::class)->status($server);

    expect($status->enabled)->toBeTrue()
        ->and($status->restartRequired)->toBeFalse()
        ->and($status->display)->toBeNull();
});

it('reports no display for a VM whose display is a serial terminal', function () {
    fakePending([['key' => 'vga', 'value' => 'serial0']]);
    [, , , $server] = createServerModel();

    $status = app(DisplayConsoleService::class)->status($server);

    // `qm vncproxy` on this VM fails with "No VNC display is present": PVE
    // starts it with `-nographic`, so QEMU has no VNC server to password.
    expect($status->enabled)->toBeFalse()
        ->and($status->display)->toBe('serial0');
});

it('reports no display for a VM whose display is switched off', function () {
    fakePending([['key' => 'vga', 'value' => 'none']]);
    [, , , $server] = createServerModel();

    expect(app(DisplayConsoleService::class)->status($server)->enabled)->toBeFalse();
});

it('judges the display type, not the options it carries', function () {
    fakePending([['key' => 'vga', 'value' => 'std,memory=32']]);
    [, , , $server] = createServerModel();

    expect(app(DisplayConsoleService::class)->status($server)->enabled)->toBeTrue();
});

it('asks for a restart when the display is changed but the guest still runs', function () {
    fakePending([['key' => 'vga', 'value' => 'serial0', 'pending' => 'std']]);
    [, , , $server] = createServerModel();

    $status = app(DisplayConsoleService::class)->status($server);

    expect($status->enabled)->toBeFalse()
        ->and($status->restartRequired)->toBeTrue();
});

it('sets the display to PVE default when enabling it', function () {
    fakePending([['key' => 'vga', 'value' => 'serial0']]);
    [, , , $server] = createServerModel();

    app(DisplayConsoleService::class)->enable($server);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && ($request['vga'] ?? null) === 'std');
});

it('does not rewrite the display of a VM that already has one', function () {
    fakePending([['key' => 'vga', 'value' => 'std']]);
    [, , , $server] = createServerModel();

    app(DisplayConsoleService::class)->enable($server);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/config')
        && $request->method() === 'POST');
});
