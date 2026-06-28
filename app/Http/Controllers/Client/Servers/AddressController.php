<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\Ipam\IpamAddressData;
use App\Models\Server;
use Spatie\LaravelData\DataCollection;

class AddressController
{
    public function __invoke(Server $server)
    {
        $addresses = $server->addresses()->with('addressBlock')->get();

        return IpamAddressData::collect($addresses, DataCollection::class);
    }
}
