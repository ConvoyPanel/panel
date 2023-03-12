<?php

use Illuminate\Support\Facades\Http;

beforeEach(fn() => Http::preventStrayRequests());

it('can sync server usages', function () {
    Http::fake([
        '*/rrddata*' => Http::response(file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetServerMetricsData.hourly.average.json')), 200),
    ]);

    [$_, $_, $node, $server] = createServerModel();

    app(\Convoy\Services\Nodes\ServerUsagesSyncService::class)->handle($node);

    $server->refresh();
    
    $this->assertEquals($server->bandwidth_usage, 7340032);
});
