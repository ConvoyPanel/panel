<?php

namespace App\Data\Server\Proxmox\Firewall;

use App\Enums\Server\Firewall\FirewallLogLevel;
use App\Enums\Server\Firewall\FirewallPolicy;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

/**
 * A server's firewall-wide settings.
 *
 * Proxmox omits unset options from the response entirely rather than returning
 * them as null, so every field here resolves its own documented default. A
 * missing `policy_in` means ACCEPT, not "unknown" -- reporting null would make
 * the UI show an empty policy on a firewall that is very much accepting
 * everything.
 */
class FirewallOptionsData extends Data
{
    public function __construct(
        /** Whether the firewall is enforced at all. Convoy-managed. */
        public bool $isEnabled,

        /** Anti-spoof IP filtering. Convoy-managed; shown read-only. */
        public bool $hasIpFilter,

        public FirewallPolicy $inboundPolicy,

        public FirewallPolicy $outboundPolicy,

        public FirewallLogLevel $inboundLogLevel,

        public FirewallLogLevel $outboundLogLevel,

        /**
         * Hash of the firewall config as read. Sent back on a write so Proxmox
         * rejects it if anything changed in between; without it two operators
         * editing at once silently overwrite each other.
         */
        public ?string $digest,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            // Unlike the rule-level field of the same name, this one is a
            // boolean in the PVE schema. It still arrives as 0/1 over JSON.
            isEnabled: (bool) Arr::get($raw, 'enable', false),
            hasIpFilter: (bool) Arr::get($raw, 'ipfilter', false),
            inboundPolicy: FirewallPolicy::from(Arr::get($raw, 'policy_in') ?: FirewallPolicy::Accept->value),
            outboundPolicy: FirewallPolicy::from(Arr::get($raw, 'policy_out') ?: FirewallPolicy::Accept->value),
            inboundLogLevel: FirewallLogLevel::from(Arr::get($raw, 'log_level_in') ?: FirewallLogLevel::NoLog->value),
            outboundLogLevel: FirewallLogLevel::from(Arr::get($raw, 'log_level_out') ?: FirewallLogLevel::NoLog->value),
            digest: Arr::get($raw, 'digest'),
        );
    }
}
