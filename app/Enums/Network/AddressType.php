<?php

namespace App\Enums\Network;

enum AddressType: string
{
    case IPV4 = 'ipv4';
    case IPV6 = 'ipv6';
}
