<?php

use Illuminate\Support\Facades\Http;

/** A VM whose display is the serial terminal, which has no VNC screen at all. */
function fakeSerialDisplay(): void
{
    fakeProxmox([
        '*/pending' => Http::response(
            ['data' => [['key' => 'vga', 'value' => 'serial0']]],
            200,
        ),
    ]);
}

it('reports the display the graphical console would attach to', function () {
    // `vga: serial0` is the standard cloud-image recipe, and it leaves the VM
    // with no VNC display -- the display console cannot work until this
    // changes, which is what the picker and the failed console both ask about.
    fakeSerialDisplay();

    [$user, $_, $_, $server] = createServerModel();

    $this->actingAs($user)
        ->getJson("/api/client/servers/{$server->uuid}/settings/hardware/display-console")
        ->assertOk()
        ->assertJsonPath('data.enabled', false)
        ->assertJsonPath('data.display', 'serial0');
});

it('can give a server a display', function () {
    fakeSerialDisplay();

    [$user, $_, $_, $server] = createServerModel();

    $this->actingAs($user)
        ->postJson("/api/client/servers/{$server->uuid}/settings/hardware/display-console")
        ->assertCreated();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && ($request['vga'] ?? null) === 'std');
});
