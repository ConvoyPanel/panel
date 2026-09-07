<?php

namespace App\Http\Requests\Admin\Settings;

use App\Enums\Mail\MailEncryption;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateMailSettingsRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            // Blank is a real, meaningful value: it clears the panel tier and hands mail back to
            // MAIL_* in the environment. Everything else is only required alongside a host, so
            // clearing the form is one action rather than seven fields of validation errors.
            'host' => 'present|nullable|string|max:255',
            'port' => 'required_with:host|nullable|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            // Absent means "keep the stored one" — the screen never receives the password, so it
            // cannot echo it back. Sending an empty string is the explicit way to clear it.
            'password' => 'sometimes|nullable|string|max:1024',
            'encryption' => ['required_with:host', 'nullable', Rule::enum(MailEncryption::class)],
            'from_address' => 'required_with:host|nullable|email|max:255',
            'from_name' => 'required_with:host|nullable|string|max:255',
        ];
    }
}
