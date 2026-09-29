<?php

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\BaseApiRequest;

class UpdatePermissionSettingsRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'allow_guest_accounts' => ['required', 'boolean'],
        ];
    }
}
