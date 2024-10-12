<?php

namespace App\Services\Activity;

use App\Enums\Network\AddressType;
use App\Models\Address;
use Illuminate\Support\Arr;
use function App\Helpers\getAddressesFromRange;

class BulkAddressCreationService
{
    public function handle(
        AddressType $type,
        string      $from,
        string      $to,
        int         $poolId,
        ?int        $serverId = null,
        int         $cidr,
        string      $gateway,
        ?string     $macAddress = null,
    ): void {
        $addresses = getAddressesFromRange($type, $from, $to);
        $existingAddresses = Address::where('address_pool_id', '=', $poolId)
                                    ->whereIn('address', $addresses)
                                    ->get('address')
                                    ->pluck('address')
                                    ->toArray();
        $addresses = array_diff($addresses, $existingAddresses);

        $transformer = function (string $address) use (
            $poolId,
            $serverId,
            $type,
            $cidr,
            $gateway,
            $macAddress,
        ) {
            return [
                'address_pool_id' => $poolId,
                'server_id' => $serverId,
                'type' => $type->value,
                'address' => $address,
                'cidr' => $cidr,
                'gateway' => $gateway,
                'mac_address' => $macAddress,
            ];
        };

        /**
         * @var array{
         *      address_pool_id: int,
         *      server_id: ?int,
         *      type: string,
         *      address: string,
         *      cidr: int,
         *      gateway: string,
         *      mac_address: ?string,
         * } $addresses
         */
        $addresses = Arr::map(
            $addresses,
            $transformer,
        );

        Address::insert($addresses);
    }
}
