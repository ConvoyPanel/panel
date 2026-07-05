<?php

use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Exceptions\Service\Server\Allocation\IsoAlreadyMountedException;
use App\Exceptions\Service\Server\Allocation\IsoAlreadyUnmountedException;
use App\Models\ISO;
use App\Models\Storage;
use App\Services\Servers\AllocationService;
use Illuminate\Support\Facades\Http;

/** Config fixture as a decoded array, optionally with extra disk lines merged in. */
function serverConfig(array $extra = []): array
{
    $config = json_decode(
        file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json')),
        true,
    );
    $config['data'] = array_merge($config['data'], $extra);

    return $config;
}

/** An ISO whose mounted volume is a deterministic "local:iso/test.iso". */
function testIso(): ISO
{
    return ISO::factory()
        ->for(Storage::factory()->create(['name' => 'local']), 'storage')
        ->create(['file_name' => 'test.iso']);
}

it('threads the config digest through mountIso and surfaces a mismatch as a 409', function () {
    Http::fake([
        '*/qemu/*/config' => Http::sequence()
            ->push(serverConfig(), 200)                     // getConfig()
            ->push(['data' => null, 'errors' => ['detected modified configuration - file changed by other user? Try again.']], 500),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    expect(fn () => app(AllocationService::class)->mountIso($server, testIso()))
        ->toThrow(ConfigModifiedException::class);
});

it('rejects mounting an ISO that is already mounted', function () {
    // The ISO is present as a cdrom disk on ide3.
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfig(['ide3' => 'local:iso/test.iso,media=cdrom']), 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    expect(fn () => app(AllocationService::class)->mountIso($server, testIso()))
        ->toThrow(IsoAlreadyMountedException::class);
});

it('unmounts a mounted ISO by deleting its interface, with the digest', function () {
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfig(['ide3' => 'local:iso/test.iso,media=cdrom']), 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    app(AllocationService::class)->unmountIso($server, testIso());

    // The delete targets the interface the ISO was actually on, and carries the
    // read's digest for optimistic concurrency.
    Http::assertSent(fn ($request) => str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && ($request['delete'] ?? null) === 'ide3'
        && ! empty($request['digest']));
});

it('rejects unmounting an ISO that is not mounted', function () {
    Http::fake([
        '*/qemu/*/config' => Http::response(serverConfig(), 200),
        '*' => Http::response(['data' => 'ok'], 200),
    ]);

    [, , , $server] = createServerModel();

    expect(fn () => app(AllocationService::class)->unmountIso($server, testIso()))
        ->toThrow(IsoAlreadyUnmountedException::class);
});
