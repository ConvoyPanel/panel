<?php

use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Services\Nodes\ServerRateLimitsSyncService;
use Convoy\Services\Servers\NetworkService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

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

    Http::assertSent(function (Request $request) {
        return $request->method() === 'POST';
    });
});

it('does not rate limit servers with a blank bandwidth limit', function () {
    $server = new Server;
    $server->setRawAttributes([
        'bandwidth_usage' => 8192,
        'bandwidth_limit' => null,
    ]);
    $node = new Node;
    $node->setRelation('servers', new Collection([$server]));

    $service = $this->mock(NetworkService::class, function (MockInterface $mock) use ($server) {
        $mock->shouldReceive('updateRateLimit')->once()->withArgs(
            fn ($actual, $rate = null) => $actual === $server && is_null($rate),
        );
    });

    (new ServerRateLimitsSyncService($service))->handle($node);
});

it('always rate limits servers with a zero bandwidth limit', function () {
    $server = new Server;
    $server->setRawAttributes([
        'bandwidth_usage' => 0,
        'bandwidth_limit' => 0,
    ]);
    $node = new Node;
    $node->setRelation('servers', new Collection([$server]));

    $service = $this->mock(NetworkService::class, function (MockInterface $mock) use ($server) {
        $mock->shouldReceive('updateRateLimit')->once()->withArgs(
            fn ($actual, $rate) => $actual === $server && $rate === 1,
        );
    });

    (new ServerRateLimitsSyncService($service))->handle($node);
});
