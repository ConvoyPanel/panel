<?php

namespace App\Data\Server\Proxmox\Firewall;

use App\Enums\Server\Firewall\RuleAction;
use App\Enums\Server\Firewall\RuleDirection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;
use Throwable;

/**
 * One line of a server's firewall log, pulled apart into readable fields.
 *
 * Proxmox hands these back as a raw iptables line -- roughly 300 characters of
 * `KEY=value` pairs -- which is unscannable in a table and needs horizontal
 * scrolling to read at all. Everything here is best-effort: the format is not
 * a documented contract, so a line that does not match leaves the structured
 * fields null and travels on its `raw` string alone. Nothing throws, because a
 * single odd line must never take out the whole log view.
 */
class FirewallLogEntryData extends Data
{
    private const TIMESTAMP_FORMAT = 'd/M/Y:H:i:s O';

    public function __construct(
        /** Proxmox's line number, and the cursor for paging. */
        public int $lineNumber,

        public ?CarbonImmutable $loggedAt,

        public ?RuleAction $action,

        public ?RuleDirection $direction,

        public ?string $sourceAddress,

        public ?string $destinationAddress,

        public ?int $sourcePort,

        public ?int $destinationPort,

        public ?string $protocol,

        /** The untouched line, always present, shown when a row is expanded. */
        public string $raw,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $line = (string) Arr::get($raw, 't', '');

        return new self(
            lineNumber: (int) Arr::get($raw, 'n', 0),
            loggedAt: self::parseTimestamp($line),
            action: self::parseAction($line),
            direction: self::parseDirection($line),
            sourceAddress: self::match('/\bSRC=(\S+)/', $line),
            destinationAddress: self::match('/\bDST=(\S+)/', $line),
            sourcePort: ($sport = self::match('/\bSPT=(\d+)/', $line)) !== null ? (int) $sport : null,
            destinationPort: ($dport = self::match('/\bDPT=(\d+)/', $line)) !== null ? (int) $dport : null,
            protocol: ($proto = self::match('/\bPROTO=(\S+)/', $line)) !== null ? strtolower($proto) : null,
            raw: $line,
        );
    }

    private static function parseTimestamp(string $line): ?CarbonImmutable
    {
        $stamp = self::match('#(\d{2}/[A-Za-z]{3}/\d{4}:\d{2}:\d{2}:\d{2} [+-]\d{4})#', $line);

        if ($stamp === null) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat(self::TIMESTAMP_FORMAT, $stamp) ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The verdict is the word immediately before the colon that separates the
     * log's own preamble from the iptables pairs, e.g. `policy DROP:` or
     * `6 tap103i0-IN ... ACCEPT:`.
     */
    private static function parseAction(string $line): ?RuleAction
    {
        $action = self::match('/\b(ACCEPT|DROP|REJECT)\b\s*:/', $line);

        return $action !== null ? RuleAction::tryFrom($action) : null;
    }

    /**
     * Direction comes from the chain name (`tap103i0-IN` / `-OUT`), which is
     * written from the guest's perspective, same as a rule's own direction.
     */
    private static function parseDirection(string $line): ?RuleDirection
    {
        $chain = self::match('/\S+-(IN|OUT)\b/', $line);

        return match ($chain) {
            'IN' => RuleDirection::Inbound,
            'OUT' => RuleDirection::Outbound,
            default => null,
        };
    }

    private static function match(string $pattern, string $subject): ?string
    {
        return preg_match($pattern, $subject, $matches) === 1 ? $matches[1] : null;
    }
}
