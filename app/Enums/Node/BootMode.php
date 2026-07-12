<?php

namespace App\Enums\Node;

enum BootMode: string
{
    case EFI = 'efi';
    case LEGACY_BIOS = 'legacy-bios';
}
