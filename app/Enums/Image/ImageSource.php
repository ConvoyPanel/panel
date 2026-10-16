<?php

namespace App\Enums\Image;

/**
 * Where a version's disks came from.
 *
 * The distinction earns its place on exactly one question: whether "a newer
 * build exists" can be answered. A manual upload has no origin to re-check, so
 * it never goes stale; a URL and a catalogue entry both do.
 */
enum ImageSource: string
{
    /** Files uploaded to the panel, which hosts them itself. */
    case MANUAL = 'manual';

    /** Origins the operator hosts and gave the panel a link to. */
    case URL = 'url';

    /** Imported from a catalogue, and re-checkable against it. */
    case REGISTRY = 'registry';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isCheckable(): bool
    {
        return $this !== self::MANUAL;
    }
}
