<?php

namespace App\Http\Requests\Client\Servers\Firewall;

use Illuminate\Foundation\Http\FormRequest;

class MoveFirewallRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageFirewall', $this->route('server'));
    }

    public function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'min:0'],
            'digest' => ['nullable', 'string', 'max:64'],
        ];
    }
}
