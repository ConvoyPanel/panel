<?php

namespace Convoy\Enums\Server\Cloudinit;

enum BiosType: string
{
    case OVMF = 'ovmf';
    case SEABIOS = 'seabios';
}
