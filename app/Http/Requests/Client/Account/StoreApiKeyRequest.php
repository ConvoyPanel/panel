<?php

namespace App\Http\Requests\Client\Account;

use App\Support\Api\AccountTokenAbilities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiKeyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|between:1,191',
            // Omit for a full-access token; otherwise scope it to specific resource abilities.
            'abilities' => 'sometimes|array',
            'abilities.*' => ['string', Rule::in(AccountTokenAbilities::all())],
        ];
    }

    /**
     * @return list<string>
     */
    public function abilities(): array
    {
        /** @var list<string> $abilities */
        $abilities = $this->input('abilities', ['*']);

        return empty($abilities) ? ['*'] : $abilities;
    }
}
