<?php

namespace App\Http\Requests\Admin\Tokens;

use App\Rules\IpAddressOrCidr;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // An empty list explicitly clears the restriction and makes the token unrestricted.
            'allowed_networks' => ['present', 'array', 'max:100'],
            'allowed_networks.*' => ['required', 'string', 'max:191', new IpAddressOrCidr],
        ];
    }

    /** @return list<string> */
    public function allowedNetworks(): array
    {
        return $this->normalizeNetworks($this->validated('allowed_networks'));
    }

    /** @param array<int, string> $networks
     * @return list<string>
     */
    private function normalizeNetworks(array $networks): array
    {
        return array_values(array_unique(array_map(trim(...), $networks)));
    }
}
