<?php

use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Models\ISO;
use App\Services\Servers\AllocationService;
use Illuminate\Support\Facades\Http;

it('threads the config digest through mountIso and surfaces a mismatch as a 409', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            // getDisks() and the free-slot scan both read the config
            ->push(
                file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json')),
                200,
            )
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
    $iso = ISO::factory()->create();

    expect(fn () => app(AllocationService::class)->mountIso($server, $iso))
        ->toThrow(ConfigModifiedException::class);
});
