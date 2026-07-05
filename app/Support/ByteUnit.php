<?php

namespace App\Support;

/**
 * Binary (1024-based) size units, matching Proxmox's single-letter suffixes.
 *
 * Central home for the byte-scaling that was previously copy-pasted as
 * `* 1024 * 1024` across the config DTOs, the rate-limit cast, and the Storage
 * model. Proxmox expresses sizes with a K/M/G/T suffix and bandwidth/memory in
 * MiB; this enum converts both directions.
 */
enum ByteUnit: string
{
    case Bytes = '';
    case Kibibytes = 'K';
    case Mebibytes = 'M';
    case Gibibytes = 'G';
    case Tebibytes = 'T';

    /** How many bytes are in one of this unit. */
    public function inBytes(): int
    {
        return match ($this) {
            self::Bytes => 1,
            self::Kibibytes => 1024,
            self::Mebibytes => 1024 ** 2,
            self::Gibibytes => 1024 ** 3,
            self::Tebibytes => 1024 ** 4,
        };
    }

    /** Scale a quantity of this unit up to whole bytes. */
    public function toBytes(int|float $quantity): int
    {
        return (int) ($quantity * $this->inBytes());
    }

    /** Scale bytes down to this unit (may be fractional). */
    public function fromBytes(int $bytes): int|float
    {
        return $bytes / $this->inBytes();
    }

    /**
     * Parse a Proxmox-style suffixed size ("32G", "4096") into bytes, or null
     * when the input isn't a plain integer with an optional K/M/G/T suffix.
     */
    public static function parseSize(string $value): ?int
    {
        if (! preg_match('/^(\d+)([KMGT]?)$/', trim($value), $matches)) {
            return null;
        }

        return self::from($matches[2])->toBytes((int) $matches[1]);
    }
}
