<?php

namespace App\Http\Controllers\Client\Servers;

use App\Models\Server;
use App\Transformers\Admin\AddressTransformer;

use function fractal;

class AddressController
{
    public function __invoke(Server $server)
    {
        $addresses = $server->addresses()->with('addressBlock')->get();

        return fractal($addresses, new AddressTransformer)->respond();
    }
}
