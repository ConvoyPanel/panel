<?php

namespace App\Http\Requests\Client\Servers\Firewall;

class StoreFirewallRuleRequest extends FirewallRuleRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // Where the rule lands in the evaluation order. Omitted means the
            // end of the list, which is what "Add rule" does by default.
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
