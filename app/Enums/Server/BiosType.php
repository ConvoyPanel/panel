<?php

namespace Convoy\Enums\Server;

enum BiosType: string
{
    case OVMF = 'ovmf';
    case SEABIOS = 'seabios';
}
