<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Models\Server;
use Convoy\Transformers\Admin\AddressTransformer;
use function fractal;

class AddressController
{
    public function __invoke(Server $server)
    {
        return fractal($server->addresses, new AddressTransformer())->respond();
    }
}
