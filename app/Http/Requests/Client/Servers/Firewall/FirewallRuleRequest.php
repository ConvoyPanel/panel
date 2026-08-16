<?php

namespace App\Http\Requests\Client\Servers\Firewall;

use App\Data\Server\Proxmox\Firewall\FirewallRuleData;
use App\Enums\Server\Firewall\FirewallLogLevel;
use App\Enums\Server\Firewall\RuleAction;
use App\Enums\Server\Firewall\RuleDirection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Shared shape for creating and editing a firewall rule.
 *
 * Both verbs take the same fields: an update replaces the rule wholesale
 * rather than patching it, so that dropping a field is expressible at all --
 * see {@see FirewallRuleData::clearedKeysAgainst()} for why that matters.
 */
abstract class FirewallRuleRequest extends FormRequest
{
    /** Protocols an `icmp-type` may accompany, per Proxmox. */
    private const ICMP_PROTOCOLS = ['icmp', 'icmpv6', 'ipv6-icmp'];

    public function authorize(): bool
    {
        return $this->user()->can('manageFirewall', $this->route('server'));
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', new Enum(RuleDirection::class)],
            'action' => ['required', new Enum(RuleAction::class)],
            'enabled' => ['required', 'boolean'],

            // A macro already carries a protocol and port, so accepting both
            // would let the UI submit a rule Proxmox then rejects for reasons
            // the user cannot see from the form.
            'macro' => ['nullable', 'string', 'max:128', 'prohibits:protocol,destination_port'],

            'protocol' => ['nullable', 'string', 'max:32'],
            'source_address' => ['nullable', 'string', 'max:512'],
            'destination_address' => ['nullable', 'string', 'max:512'],
            'source_port' => ['nullable', 'string', 'max:512'],
            'destination_port' => ['nullable', 'string', 'max:512'],

            // Proxmox only accepts this alongside an ICMP protocol, and
            // rejects the whole rule otherwise.
            'icmp_type' => [
                'nullable',
                'string',
                'max:64',
                Rule::prohibitedIf(fn () => ! in_array(
                    strtolower((string) $this->input('protocol')),
                    self::ICMP_PROTOCOLS,
                    true,
                )),
            ],

            // For a guest, `iface` must name one of its own network devices.
            'interface' => ['nullable', 'string', 'regex:/^net\d+$/'],

            'log_level' => ['nullable', new Enum(FirewallLogLevel::class)],
            'comment' => ['nullable', 'string', 'max:255'],

            // The digest the client last read the ruleset at. Positions
            // renumber on every write, so without this a delete or edit aimed
            // at "rule 2" can land on a rule the user never saw.
            'digest' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * The validated rule, in domain terms.
     */
    public function toRuleData(): FirewallRuleData
    {
        return new FirewallRuleData(
            position: null,
            direction: RuleDirection::from($this->validated('direction')),
            action: RuleAction::from($this->validated('action')),
            isEnabled: $this->boolean('enabled'),
            macro: $this->validated('macro'),
            protocol: $this->validated('protocol'),
            sourceAddress: $this->validated('source_address'),
            destinationAddress: $this->validated('destination_address'),
            sourcePort: $this->validated('source_port'),
            destinationPort: $this->validated('destination_port'),
            icmpType: $this->validated('icmp_type'),
            interface: $this->validated('interface'),
            logLevel: ($level = $this->validated('log_level')) ? FirewallLogLevel::from($level) : null,
            comment: $this->validated('comment'),
            digest: $this->validated('digest'),
        );
    }
}
