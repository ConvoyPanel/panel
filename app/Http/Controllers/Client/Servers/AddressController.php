<?php

namespace App\Http\Controllers\Client\Servers;

use App\Models\Server;
use App\Transformers\Admin\AddressTransformer;
use function fractal;

class AddressController
{
    public function __invoke(Server $server)
    {
        return fractal($server->addresses, new AddressTransformer())->respond();
    }
}
