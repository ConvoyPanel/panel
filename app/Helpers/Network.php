<?php

namespace App\Helpers;

use App\Enums\Network\AddressType;
use GMP;

function ipv6ToInteger(string $ip): GMP
{
    return gmp_import(inet_pton($ip));
}

function ipv6FromInteger(GMP $integer): ?string
{
    $ip = inet_ntop(str_pad(gmp_export($integer), 16, "\0", STR_PAD_LEFT));

    return $ip !== false ? $ip : null;
}

/**
 * @return string[]
 */
function getAddressesFromRange(AddressType $type, string $from, string $to): array
{
    /** @var string[] */
    $addresses = [];

    if ($type === AddressType::IPV4) {
        $from = ip2long($from);
        $to = ip2long($to);

        for ($i = $from; $i <= $to; $i++) {
            $addresses[] = long2ip($i);
        }
    } else {
        $from = ipv6ToInteger($from);
        $to = ipv6ToInteger($to);

        for ($i = $from; $i <= $to; $i++) {
            $addresses[] = ipv6FromInteger($i);
        }
    }

    return $addresses;
}
