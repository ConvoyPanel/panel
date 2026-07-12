<?php

use App\Data\Server\OveragePenaltyData;
use App\Enums\Server\OveragePenaltyAction;
use App\Services\Servers\OveragePenaltyResolver;
use App\Settings\BandwidthSettings;

beforeEach(function () {
    BandwidthSettings::fake([
        'overage_action' => 'throttle',
        'overage_rate' => 1_000_000,
    ]);
});

it('falls back to the global settings default', function () {
    [, , , $server] = createServerModel();

    $penalty = app(OveragePenaltyResolver::class)->for($server);

    expect($penalty->action)->toBe(OveragePenaltyAction::THROTTLE)
        ->and($penalty->rate)->toBe(1_000_000);
});

it('prefers the node override over the global default', function () {
    [, , $node, $server] = createServerModel();
    $node->update(['overage_penalty' => OveragePenaltyData::disconnect()]);

    $penalty = app(OveragePenaltyResolver::class)->for($server->load('node'));

    expect($penalty->action)->toBe(OveragePenaltyAction::DISCONNECT);
});

it('prefers the server override over node and global', function () {
    [, , $node, $server] = createServerModel();
    $node->update(['overage_penalty' => OveragePenaltyData::disconnect()]);
    $server->update(['overage_penalty' => OveragePenaltyData::throttle(5_000_000)]);

    $penalty = app(OveragePenaltyResolver::class)->for($server->fresh()->load('node'));

    expect($penalty->action)->toBe(OveragePenaltyAction::THROTTLE)
        ->and($penalty->rate)->toBe(5_000_000);
});
