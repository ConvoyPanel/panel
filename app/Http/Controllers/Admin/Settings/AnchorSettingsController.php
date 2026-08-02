<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Data\Admin\Settings\AnchorSettingsData;
use App\Http\Requests\Admin\Settings\UpdateAnchorSettingsRequest;
use App\Settings\AnchorSettings;

class AnchorSettingsController
{
    public function show(AnchorSettings $settings): AnchorSettingsData
    {
        return new AnchorSettingsData($settings->panel_url ?: null);
    }

    public function update(
        UpdateAnchorSettingsRequest $request,
        AnchorSettings $settings,
    ): AnchorSettingsData {
        // Stored as '' rather than null so the setting's shape never changes;
        // an empty value is what makes the cascade fall through to APP_URL.
        $settings->panel_url = rtrim((string) $request->input('panel_url'), '/');
        $settings->save();

        return new AnchorSettingsData($settings->panel_url ?: null);
    }
}
