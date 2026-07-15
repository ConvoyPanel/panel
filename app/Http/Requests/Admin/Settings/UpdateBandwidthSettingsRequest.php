<?php

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\BaseApiRequest;

class UpdateBandwidthSettingsRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            // Required, not nullable: the global tier is the bottom of the
            // cascade and must always resolve to a concrete penalty.
            'overage_penalty' => 'required|array',
            'overage_penalty.action' => 'required|string|in:throttle,disconnect',
            // A rate is only meaningful for a throttle; `disconnect` keeps the
            // stored rate untouched so toggling back doesn't lose it.
            'overage_penalty.rate' => 'required_if:overage_penalty.action,throttle|integer|min:1',
        ];
    }
}
