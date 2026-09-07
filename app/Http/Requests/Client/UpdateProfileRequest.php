<?php

namespace App\Http\Requests\Client;

use App\Services\Users\AccountPolicyResolver;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * The operator may take self-service name changes away — typically because a billing system or
     * directory upstream owns the account's name and an edit here would only desync it.
     *
     * Enforced here rather than by hiding the field: the account screen does hide it, but that is
     * a courtesy to the person, not the boundary.
     */
    public function authorize(): bool
    {
        // Resolved rather than injected: kept in step with its siblings, one of which extends a base class that fixes this signature.
        return app(AccountPolicyResolver::class)->for($this->user())->canChangeName;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'between:1,191'],
        ];
    }
}
