<?php

namespace App\Enums\Server\Backup;

use Illuminate\Support\Str;

enum BackupErrorCode: string
{
    case STORAGE_EXCEEDED = 'storage_exceeded';
    case TIMEOUT = 'timeout';
    case OTHER = 'other';

    /**
     * Substring => code mappings, checked in order. Matched against the raw
     * failure text pulled from the Proxmox task log. Deliberately loose: a
     * miss just falls through to OTHER, which is always safe to show.
     */
    private const MAPPINGS = [
        /** Storage / quota exhaustion */
        'no space left' => self::STORAGE_EXCEEDED,
        'not enough space' => self::STORAGE_EXCEEDED,
        'quota exceeded' => self::STORAGE_EXCEEDED,
        'storage is full' => self::STORAGE_EXCEEDED,

        /** Timeouts (incl. the orphan-prune message) */
        'timed out' => self::TIMEOUT,
        'timeout' => self::TIMEOUT,
        'did not complete in time' => self::TIMEOUT,
    ];

    /**
     * Classify a raw backup failure message into a stable, client-safe code.
     */
    public static function classify(string $message): self
    {
        foreach (self::MAPPINGS as $needle => $code) {
            if (Str::contains($message, $needle, ignoreCase: true)) {
                return $code;
            }
        }

        return self::OTHER;
    }
}
