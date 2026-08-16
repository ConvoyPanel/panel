<?php

namespace App\Http\Requests\Client\Servers\Firewall;

use App\Enums\Server\Firewall\FirewallLogLevel;
use App\Enums\Server\Firewall\FirewallPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * The firewall options a tenant may set.
 *
 * `enable` and `ipfilter` are absent on purpose: the network sync rewrites
 * both on every address change and rebuild, so offering them here would be a
 * control that silently reverts.
 */
class UpdateFirewallOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageFirewall', $this->route('server'));
    }

    public function rules(): array
    {
        return [
            'inbound_policy' => ['required', new Enum(FirewallPolicy::class)],
            'outbound_policy' => ['required', new Enum(FirewallPolicy::class)],
            'inbound_log_level' => ['required', new Enum(FirewallLogLevel::class)],
            'outbound_log_level' => ['required', new Enum(FirewallLogLevel::class)],

            // The digest the client last read. Optional so an API consumer can
            // opt out of the check, but the UI always sends it.
            'digest' => ['nullable', 'string', 'max:64'],
        ];
    }
}
