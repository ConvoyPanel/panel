<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Data\Admin\Settings\BandwidthSettingsData;
use App\Enums\Server\OveragePenaltyAction;
use App\Http\Requests\Admin\Settings\UpdateBandwidthSettingsRequest;
use App\Services\Servers\OveragePenaltyResolver;
use App\Settings\BandwidthSettings;

class BandwidthSettingsController
{
    public function show(OveragePenaltyResolver $resolver): BandwidthSettingsData
    {
        // Read through the resolver so the screen and the enforcement path agree
        // on how the stored strings become a typed penalty.
        return new BandwidthSettingsData($resolver->global());
    }

    public function update(
        UpdateBandwidthSettingsRequest $request,
        BandwidthSettings $settings,
        OveragePenaltyResolver $resolver,
    ): BandwidthSettingsData {
        $action = OveragePenaltyAction::from($request->input('overage_penalty.action'));

        $settings->overage_action = $action->value;

        // Leave the stored rate alone for `disconnect` — it is not part of that
        // penalty, and preserving it means flipping back to throttle restores
        // the operator's previous figure instead of a default.
        if ($action === OveragePenaltyAction::THROTTLE) {
            $settings->overage_rate = (int) $request->input('overage_penalty.rate');
        }

        $settings->save();

        return new BandwidthSettingsData($resolver->global());
    }
}
