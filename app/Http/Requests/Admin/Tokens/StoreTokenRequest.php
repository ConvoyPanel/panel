<?php

namespace App\Http\Requests\Admin\Tokens;

use App\Support\Api\TokenAbilities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|between:1,191',
            // Omit for a full-access token; otherwise scope it to specific resource abilities.
            'abilities' => 'sometimes|array',
            'abilities.*' => ['string', Rule::in(TokenAbilities::all())],
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
