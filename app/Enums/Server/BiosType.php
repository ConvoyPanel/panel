<?php

namespace App\Enums\Server;

enum BiosType: string
{
    case OVMF = 'ovmf';
    case SEABIOS = 'seabios';
}
