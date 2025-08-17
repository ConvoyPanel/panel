<?php

namespace App\Services\Addresses;

use App\Enums\Network\AddressVersion;
use App\Models\NetworkInterface;
use GMP;

class AddressAvailabilityService
{
    public function hasSufficientAddresses(int $networkInterfaceId, int $requestedIpv4, int $requestedIpv6): bool
    {
        $networkInterface = NetworkInterface::with([
            'addressBlockGroups.addressBlocks' => fn ($query) => $query->withCount(['addresses' => fn ($q) => $q->whereNotNull('server_id')]),
        ])->findOrFail($networkInterfaceId);

        $availableIpv4 = gmp_init(0);
        $availableIpv6 = gmp_init(0);

        foreach ($networkInterface->addressBlockGroups as $group) {
            foreach ($group->addressBlocks as $block) {
                $isV4 = $block->version === AddressVersion::IPv4;
                $totalAddressSpace = gmp_pow(2, ($isV4 ? 32 : 128) - $block->prefix_length_from);

                $allocatedCount = gmp_init($block->addresses_count);
                $sizeOfSingleAllocation = gmp_pow(2, ($isV4 ? 32 : 128) - $block->prefix_length_to);
                $allocatedAddressSpace = gmp_mul($allocatedCount, $sizeOfSingleAllocation);

                $available = gmp_sub($totalAddressSpace, $allocatedAddressSpace);

                if ($isV4) {
                    $availableIpv4 = gmp_add($availableIpv4, $available);
                } else {
                    $availableIpv6 = gmp_add($availableIpv6, $available);
                }

                if (gmp_cmp($availableIpv4, $requestedIpv4) >= 0 && gmp_cmp($availableIpv6, $requestedIpv6) >= 0) {
                    return true;
                }
            }
        }

        return gmp_cmp($availableIpv4, $requestedIpv4) >= 0 && gmp_cmp($availableIpv6, $requestedIpv6) >= 0;
    }
}
