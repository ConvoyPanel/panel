<?php

namespace App\Enums\Network;

enum AddressVersion: string
{
    case IPv4 = 'ipv4';
    case IPv6 = 'ipv6';
}
