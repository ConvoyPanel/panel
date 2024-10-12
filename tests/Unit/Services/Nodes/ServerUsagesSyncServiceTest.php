<?php

use Carbon\Carbon;
use App\Services\Nodes\ServerUsagesSyncService;
use Illuminate\Support\Facades\Http;

it('can sync server usages', function () {
    Http::fake([
        '*/rrddata*' => Http::response(
            file_get_contents(
                base_path(
                    'tests/Fixtures/Repositories/Server/GetServerMetricsData.hourly.average.json',
                ),
            ),
            200,
        ),
    ]);

    [$_, $_, $node, $server] = createServerModel();

    // generate a carbon date on the first of March 2023
    $server->update([
        'hydrated_at' => Carbon::create(2023, 3, 1, 0, 0, 0, 'UTC'),
    ]);

    app(ServerUsagesSyncService::class)->handle($node);

    $server->refresh();

    expect($server->bandwidth_usage)->toBe(7340032);
});
