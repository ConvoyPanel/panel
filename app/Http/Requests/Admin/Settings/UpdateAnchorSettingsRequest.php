<?php

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\BaseApiRequest;

class UpdateAnchorSettingsRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            // Nullable, unlike the bandwidth tier: there is a sensible thing
            // below this one (APP_URL), so clearing the field is meaningful.
            'panel_url' => 'nullable|url:http,https|max:2048',
        ];
    }
}
