<?php

namespace App\Services\Addresses;

use App\Exceptions\Service\Address\InsufficientAddressesException;
use App\Models\NetworkInterface;
use Illuminate\Support\Collection;
use App\Enums\Network\AddressVersion;
use App\Models\Address;
use IPLib\Factory as IPFactory;

class AddressAllocationService
{
    /**
     * @return Collection<Address>
     */
    public function handle(int $networkInterfaceId, int $requestedIpv4, int $requestedIpv6): Collection
    {
        $networkInterface = NetworkInterface::with([
            'addressBlockGroups.addressBlocks.addresses' => fn ($query) => $query->whereNotNull('server_id'),
        ])->findOrFail($networkInterfaceId);

        $allocatedAddresses = new Collection();
        $foundIpv4 = 0;
        $foundIpv6 = 0;

        foreach ($networkInterface->addressBlockGroups as $group) {
            foreach ($group->addressBlocks as $block) {
                if ($foundIpv4 >= $requestedIpv4 && $foundIpv6 >= $requestedIpv6) {
                    break 2;
                }

                $isV4 = $block->version === AddressVersion::IPv4;
                if (($isV4 && $foundIpv4 >= $requestedIpv4) || (!$isV4 && $foundIpv6 >= $requestedIpv6)) {
                    continue;
                }

                $range = IPFactory::parseRangeString($block->base_ip . '/' . $block->prefix_length_from);
                if (!$range) {
                    continue;
                }

                $allocatedIps = new Collection($block->addresses->pluck('ip')->all());

                for ($i = 0; $i < $range->getSize(); ++$i) {
                    if ($isV4 && $foundIpv4 >= $requestedIpv4) break;
                    if (!$isV4 && $foundIpv6 >= $requestedIpv6) break;

                    $addressAtOffset = $range->getAddressAtOffset($i);
                    if ($addressAtOffset && !$allocatedIps->contains($addressAtOffset->toString())) {
                        $address = Address::firstOrCreate([
                            'address_block_id' => $block->id,
                            'ip' => $addressAtOffset->toString(),
                        ], [
                            'prefix_length' => $block->prefix_length_to,
                        ]);
                        $address->setRelation('addressBlock', $block);
                        $allocatedAddresses->push($address);

                        if ($isV4) {
                            $foundIpv4++;
                        } else {
                            $foundIpv6++;
                        }
                    }
                }
            }
        }

        if ($foundIpv4 < $requestedIpv4 || $foundIpv6 < $requestedIpv6) {
            throw new InsufficientAddressesException();
        }

        return $allocatedAddresses;
    }
}
