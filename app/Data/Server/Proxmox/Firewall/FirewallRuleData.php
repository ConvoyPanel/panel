<?php

namespace App\Data\Server\Proxmox\Firewall;

use App\Enums\Server\Firewall\FirewallLogLevel;
use App\Enums\Server\Firewall\RuleAction;
use App\Enums\Server\Firewall\RuleDirection;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

/**
 * One rule in a server's Proxmox firewall ruleset.
 *
 * Proxmox returns these as flat JSON objects, so this maps by hand the way
 * {@see \App\Data\Server\Proxmox\Backup\BackupData} does. It deliberately does
 * NOT use MapsProxmoxProperties / #[ProxmoxProperty] -- that codec parses the
 * `key=value` tail of a property list (`net0`, `scsi0`), which is a different
 * wire shape entirely.
 */
class FirewallRuleData extends Data
{
    /**
     * Modelled optional fields, as `DTO property => PVE key`.
     *
     * Drives both directions of the mapping and, more importantly,
     * {@see clearedKeysAgainst()}: Proxmox does not treat an empty string as
     * "unset", so removing a comment or a port means naming the key in the
     * request's `delete` list. Keeping one list means a new field can never be
     * writable but un-clearable.
     */
    public const OPTIONAL_KEYS = [
        'macro' => 'macro',
        'protocol' => 'proto',
        'sourceAddress' => 'source',
        'destinationAddress' => 'dest',
        'sourcePort' => 'sport',
        'destinationPort' => 'dport',
        'icmpType' => 'icmp-type',
        'interface' => 'iface',
        'logLevel' => 'log',
        'comment' => 'comment',
    ];

    public function __construct(
        /** Index in the ruleset. Null for a rule that has not been created yet. */
        public ?int $position,

        public RuleDirection $direction,

        public RuleAction $action,

        public bool $isEnabled,

        /** Predefined Proxmox macro (`SSH`, `HTTP`, ...) standing in for protocol + port. */
        public ?string $macro,

        public ?string $protocol,

        /** An address, CIDR, range, comma-list, alias name, or `+ipset` reference. */
        public ?string $sourceAddress,

        public ?string $destinationAddress,

        /** A port, a `80:85` range, or a comma-separated list of either. */
        public ?string $sourcePort,

        public ?string $destinationPort,

        public ?string $icmpType,

        /** A `net0`-style device name, restricting the rule to one interface. */
        public ?string $interface,

        public ?FirewallLogLevel $logLevel,

        public ?string $comment,

        /**
         * Hash of the firewall config this rule was read from, sent back on a
         * write so Proxmox refuses it if anything moved in between.
         *
         * This matters more here than it does for options: a rule's identity
         * is its index, and indices renumber on every insert and delete.
         * Without the digest, deleting "rule 2" after someone else inserted
         * one above it deletes a different rule than the one the user saw.
         */
        public ?string $digest,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            position: Arr::get($raw, 'pos'),
            direction: RuleDirection::from(Arr::get($raw, 'type')),
            action: RuleAction::from(Arr::get($raw, 'action')),
            // Rule-level `enable` is an integer, unlike the boolean of the same
            // name in firewall options. Absent means enabled, matching Proxmox.
            isEnabled: (bool) Arr::get($raw, 'enable', 1),
            macro: Arr::get($raw, 'macro'),
            protocol: Arr::get($raw, 'proto'),
            sourceAddress: Arr::get($raw, 'source'),
            destinationAddress: Arr::get($raw, 'dest'),
            sourcePort: Arr::get($raw, 'sport'),
            destinationPort: Arr::get($raw, 'dport'),
            icmpType: Arr::get($raw, 'icmp-type'),
            interface: Arr::get($raw, 'iface'),
            logLevel: ($level = Arr::get($raw, 'log')) ? FirewallLogLevel::from($level) : null,
            comment: Arr::get($raw, 'comment'),
            digest: Arr::get($raw, 'digest'),
        );
    }

    /**
     * The PVE-shaped body for a create or update request.
     *
     * Null properties are omitted rather than sent empty -- an empty string
     * does not clear a field in Proxmox, it just fails differently.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = [
            'type' => $this->direction->value,
            'action' => $this->action->value,
            'enable' => (int) $this->isEnabled,
        ];

        foreach (self::OPTIONAL_KEYS as $property => $key) {
            $value = $this->{$property};

            if ($value === null || $value === '') {
                continue;
            }

            $payload[$key] = $value instanceof \BackedEnum ? $value->value : $value;
        }

        return $payload;
    }

    /**
     * PVE keys that $previous had set and this rule does not, i.e. the ones an
     * update has to explicitly `delete` rather than merely omit.
     *
     * @return list<string>
     */
    public function clearedKeysAgainst(self $previous): array
    {
        $cleared = [];

        foreach (self::OPTIONAL_KEYS as $property => $key) {
            $wasSet = $previous->{$property} !== null && $previous->{$property} !== '';
            $isSet = $this->{$property} !== null && $this->{$property} !== '';

            if ($wasSet && ! $isSet) {
                $cleared[] = $key;
            }
        }

        return $cleared;
    }
}
