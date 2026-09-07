<?php

namespace App\Http\Requests\Client;

use App\Services\Users\AccountPolicyResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailRequest extends FormRequest
{
    /**
     * {@see UpdateProfileRequest::authorize()} — the same policy, and the switch operators are
     * likeliest to reach for: the email is what an upstream billing system keys the account on.
     */
    public function authorize(): bool
    {
        // Resolved rather than injected: kept in step with its siblings, one of which extends a base class that fixes this signature.
        return app(AccountPolicyResolver::class)->for($this->user())->canChangeEmail;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'between:1,191',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
        ];
    }
}
