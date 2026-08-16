<?php

namespace App\Http\Requests\Client\Servers\Firewall;

use Illuminate\Foundation\Http\FormRequest;

class DeleteFirewallRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageFirewall', $this->route('server'));
    }

    public function rules(): array
    {
        return [
            // Optional, but the UI always sends it: positions renumber on
            // every write, so a stale index would otherwise delete whichever
            // rule has since taken that slot.
            'digest' => ['nullable', 'string', 'max:64'],
        ];
    }
}
