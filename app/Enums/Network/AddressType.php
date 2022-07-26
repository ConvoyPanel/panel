<?php

namespace App\Enums\Network;

enum AddressType: string
{
    case IPV4 = 'ip';
    case GWV4 = 'gw'; //gateway

    case IPV6 = 'ip6';
    case GWV6 = 'gw6';
}