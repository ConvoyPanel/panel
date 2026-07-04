<?php

use App\Services\Nodes\ServerRateLimitsSyncService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('can rate limit servers if over limit', function () {
    Http::fake([
        '/api2/json/nodes/*/qemu/*/config' => Http::sequence()
            ->push(
                file_get_contents(
                    base_path(
                        'tests/Fixtures/Repositories/Server/GetServerConfigData.json',
                    ),
                ),
                200
            )
            ->push(
                file_get_contents(
                    base_path(
                        'tests/Fixtures/Repositories/Server/GetServerConfigData.json',
                    ),
                ),
                200,
            )
            ->push(['data' => 'dummy-upid'], 200),

    ]);

    [$_, $_, $node, $server] = createServerModel();

    $server->update([
        'bandwidth_usage' => 8192,
        'bandwidth_limit' => 4092,
    ]);

    app(ServerRateLimitsSyncService::class)->handle($node);

    // The config update must echo the digest read from getConfig so PVE can
    // reject a stale write (optimistic concurrency).
    Http::assertSent(function (Request $request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/config')
            && $request['digest'] === '47fb9b586691ee1d97020370b9e7bfc8382a2db6';
    });
});
