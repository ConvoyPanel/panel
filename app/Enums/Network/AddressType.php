<?php

namespace Convoy\Enums\Network;

enum AddressType: string
{
    case IPV4 = 'ipv4';
    case GWV4 = 'gw'; //gateway

    case IPV6 = 'ipv6';
    case GWV6 = 'gw6';
}