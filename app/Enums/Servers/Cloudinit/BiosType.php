<?php

namespace Convoy\Enums\Servers\Cloudinit;

enum BiosType: string
{
    case OVMF = 'ovmf';
    case SEABIOS = 'seabios';
}
