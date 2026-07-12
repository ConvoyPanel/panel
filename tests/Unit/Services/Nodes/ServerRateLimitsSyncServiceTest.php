<?php

use App\Models\Server;
use App\Services\Nodes\ServerRateLimitsSyncService;
use App\Settings\BandwidthSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function syncRateLimit(Server $server): void
{
    app(ServerRateLimitsSyncService::class)->sync($server->fresh()->load('node'));
}

function assertConfigWrite(callable $matcher): void
{
    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && str_contains($r->url(), '/config')
        && $matcher($r));
}

it('throttles an over-quota server to the resolved penalty rate', function () {
    BandwidthSettings::fake(['overage_action' => 'throttle', 'overage_rate' => 2_000_000]);
    fakeProxmox();

    [, , , $server] = createServerModel();
    $server->update(['bandwidth_usage' => 100 * 1048576, 'bandwidth_limit' => 50 * 1048576]);

    syncRateLimit($server);

    // 2_000_000 bytes/s -> Proxmox decimal "rate=2", and the write echoes the digest.
    assertConfigWrite(fn (Request $r) => str_contains($r['net0'] ?? '', 'rate=2')
        && $r['digest'] === '47fb9b586691ee1d97020370b9e7bfc8382a2db6');
});

it('disconnects an over-quota server on a disconnect penalty', function () {
    BandwidthSettings::fake(['overage_action' => 'disconnect', 'overage_rate' => 1_000_000]);
    fakeProxmox();

    [, , , $server] = createServerModel();
    $server->update(['bandwidth_usage' => 100 * 1048576, 'bandwidth_limit' => 50 * 1048576]);

    syncRateLimit($server);

    assertConfigWrite(fn (Request $r) => str_contains($r['net0'] ?? '', 'link_down=1'));
});

it('enforces the persistent speed cap when under quota', function () {
    fakeProxmox();

    [, , , $server] = createServerModel();
    $server->update(['bandwidth_usage' => 0, 'bandwidth_limit' => -1, 'speed_limit' => 100_000_000]);

    syncRateLimit($server);

    assertConfigWrite(fn (Request $r) => str_contains($r['net0'] ?? '', 'rate=100'));
});

it('issues no write when under quota with no speed cap (idempotent)', function () {
    fakeProxmox();

    [, , , $server] = createServerModel();
    $server->update(['bandwidth_usage' => 0, 'bandwidth_limit' => -1, 'speed_limit' => null]);

    syncRateLimit($server);

    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST' && str_contains($r->url(), '/config'));
});
