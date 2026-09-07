<?php

namespace App\Enums\Image;

/**
 * What a disk in an image version is for.
 *
 * The role is what tells a consumer which `qm create` argument a disk becomes:
 * a system disk is the boot volume and lands on the definition's own disk slot,
 * while a varstore is passed as `efidisk0` and only exists for OVMF images. That
 * distinction cannot be read off the file -- both are just qcow2 -- so it is
 * recorded when the version is built.
 */
enum ImageDiskRole: string
{
    case SYSTEM = 'system';
    case EFIVARS = 'efivars';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
