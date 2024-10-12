<?php

namespace Convoy\Transformers\Client;

use Convoy\Data\Server\Proxmox\ServerProxmoxData;
use League\Fractal\TransformerAbstract;

class ServerDetailTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     */
    public function transform(ServerProxmoxData $server): array
    {
        $data = $server->toArray();

        $data['internal_id'] = $data['id'];
        $data['id'] = $data['uuid_short'];
        unset($data['uuid_short']);

        return $data;
    }
}
