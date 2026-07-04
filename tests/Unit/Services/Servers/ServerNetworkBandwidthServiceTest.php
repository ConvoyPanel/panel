<?php

use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Services\Servers\ServerNetworkBandwidthService;
use Illuminate\Support\Facades\Http;

it('surfaces a proxmox digest mismatch as a ConfigModifiedException', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            // getConfig() — carries the digest we will echo back
            ->push(
                file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json')),
                200,
            )
            // update() — Proxmox rejects the write because the config changed
            ->push(
                ['data' => null, 'errors' => ['detected modified configuration - file changed by other user? Try again.']],
                500,
            ),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    expect(fn () => app(ServerNetworkBandwidthService::class)->setRateLimit($server, 1))
        ->toThrow(ConfigModifiedException::class);
});
