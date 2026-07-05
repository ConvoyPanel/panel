<?php

use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Services\Servers\CloudinitService;
use Illuminate\Support\Facades\Http;

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
