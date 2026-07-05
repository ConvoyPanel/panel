<?php

namespace App\Support;

use App\Enums\Network\AddressVersion;
use GMP;

/**
 * IP address helpers (v4/v6 range expansion and IPv6 <-> integer conversion).
 *
 * NOTE: currently unused on `next` — the IPAM overhaul replaced the old
 * range-expansion path. Kept here (relocated from the deleted app/Helpers) as
 * a reusable utility; safe to drop if it stays unreferenced.
 */
class Network
{
    public static function ipv6ToInteger(string $ip): GMP
    {
        return gmp_import(inet_pton($ip));
    }

    public static function ipv6FromInteger(GMP $integer): ?string
    {
        $ip = inet_ntop(str_pad(gmp_export($integer), 16, "\0", STR_PAD_LEFT));

        return $ip !== false ? $ip : null;
    }

    /**
     * @return string[]
     */
    public static function getAddressesFromRange(AddressVersion $type, string $from, string $to): array
    {
        /** @var string[] */
        $addresses = [];

        if ($type === AddressVersion::IPv4) {
            $from = ip2long($from);
            $to = ip2long($to);

            for ($i = $from; $i <= $to; $i++) {
                $addresses[] = long2ip($i);
            }
        } else {
            $from = self::ipv6ToInteger($from);
            $to = self::ipv6ToInteger($to);

            for ($i = $from; $i <= $to; $i++) {
                $addresses[] = self::ipv6FromInteger($i);
            }
        }

        return $addresses;
    }
}
