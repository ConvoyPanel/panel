<?php

namespace Convoy\Services\Nodes\Addresses;

use Convoy\Enums\Network\AddressType;

class AddressHelpers
{
    public function expandIpRange(AddressType $ipType, string $startIp, string $endIp) {
        // Validate the IP addresses
        if (!filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $ipType === AddressType::IPV4) {
            return false; // Invalid IPv4 address
        }
        if (!filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $ipType === AddressType::IPV4) {
            return false; // Invalid IPv4 address
        }
        if (!filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $ipType === AddressType::IPV6) {
            return false; // Invalid IPv6 address
        }
        if (!filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $ipType === AddressType::IPV6) {
            return false; // Invalid IPv6 address
        }

        $expandedIpRange = [];

        // Convert IPv4-mapped IPv6 addresses to IPv4 if necessary
        if ($ipType === IpType::IPv6 && strpos($startIp, '::ffff:') === 0) {
            $startIp = substr($startIp, 7);
        }
        if ($ipType === IpType::IPv6 && strpos($endIp, '::ffff:') === 0) {
            $endIp = substr($endIp, 7);
        }

        if ($ipType === IpType::IPv6) {
            $startIp = inet_pton($startIp);
            $endIp = inet_pton($endIp);
        }

        for ($i = $startIp; $i <= $endIp; $i++) {
            $expandedIpRange[] = ($ipType === IpType::IPv6) ? inet_ntop($i) : long2ip($i);
        }

        return $expandedIpRange;
    }
}