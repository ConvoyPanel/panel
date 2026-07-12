<?php

use App\Data\Server\OveragePenaltyData;
use App\Enums\Server\OveragePenaltyAction;
use Carbon\Carbon;

it('is never over quota when the limit is unlimited (-1)', function () {
    [, , , $server] = createServerModel();
    $server->bandwidth_limit = -1;
    $server->bandwidth_usage = 500 * 1048576;

    expect($server->isOverBandwidthQuota())->toBeFalse();
});

it('is over quota once usage meets the limit', function () {
    [, , , $server] = createServerModel();
    $server->bandwidth_limit = 100 * 1048576;

    $server->bandwidth_usage = 100 * 1048576;
    expect($server->isOverBandwidthQuota())->toBeTrue();

    $server->bandwidth_usage = 10 * 1048576;
    expect($server->isOverBandwidthQuota())->toBeFalse();
});

it('resolves the reset day from the stored anchor, else the creation day', function () {
    [, , , $server] = createServerModel();

    $server->bandwidth_reset_day = 15;
    expect($server->bandwidthResetDay())->toBe(15);

    $server->bandwidth_reset_day = null;
    $server->created_at = Carbon::parse('2026-03-09 10:00:00');
    expect($server->bandwidthResetDay())->toBe(9);
});

it('round-trips the overage penalty override through the cast', function () {
    [, , , $server] = createServerModel();

    $server->update(['overage_penalty' => OveragePenaltyData::throttle(2_000_000)]);
    $fresh = $server->fresh();

    expect($fresh->overage_penalty)->toBeInstanceOf(OveragePenaltyData::class)
        ->and($fresh->overage_penalty->action)->toBe(OveragePenaltyAction::THROTTLE)
        ->and($fresh->overage_penalty->rate)->toBe(2_000_000);

    $server->update(['overage_penalty' => null]);
    expect($server->fresh()->overage_penalty)->toBeNull();
});
