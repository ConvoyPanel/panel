<?php

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\BaseApiRequest;

class UpdateAccountSettingsRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            // All three required rather than `sometimes`: the screen is one form
            // over the whole policy, so a partial body is a bug in the caller
            // rather than a request to leave a switch alone.
            'allow_name_change' => 'required|boolean',
            'allow_email_change' => 'required|boolean',
            'allow_password_change' => 'required|boolean',
        ];
    }
}
