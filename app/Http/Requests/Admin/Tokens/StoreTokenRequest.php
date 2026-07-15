<?php

namespace App\Http\Requests\Admin\Tokens;

use App\Rules\IpAddressOrCidr;
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
            'allowed_networks' => ['sometimes', 'array', 'max:100'],
            'allowed_networks.*' => ['required', 'string', 'max:191', new IpAddressOrCidr],
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

    /** @return list<string> */
    public function allowedNetworks(): array
    {
        /** @var array<int, string> $networks */
        $networks = $this->validated('allowed_networks', []);

        return array_values(array_unique(array_map(trim(...), $networks)));
    }
}
