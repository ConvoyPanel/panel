<?php

namespace App\Enums\Server\Disk;

enum DiskTranslationMode: string
{
    case NONE = 'none';
    case LBA = 'lba';
    case AUTO = 'auto';
}
