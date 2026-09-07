<?php

namespace App\Http\Requests\Admin\Settings;

use App\Enums\Mail\MailEncryption;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class TestMailSettingsRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            // The whole form, because the point is to test credentials *before* committing them.
            // Validating against the unsaved body is what makes the button worth pressing.
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            // Omitted means "use the password already stored", so an admin can test a host or
            // port change without retyping a secret the screen never showed them.
            'password' => 'sometimes|nullable|string|max:1024',
            'encryption' => ['required', Rule::enum(MailEncryption::class)],
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            // Defaults to the acting admin's own address in the controller.
            'recipient' => 'sometimes|nullable|email|max:255',
        ];
    }
}
