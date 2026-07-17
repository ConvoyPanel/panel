<?php

namespace App\Http\Controllers\Admin\Nodes;

use App\Data\Node\Status\NodeStatusData;
use App\Enums\Node\Testing\ConnectionErrorCode;
use App\Exceptions\Http\Node\NodeUnreachableException;
use App\Exceptions\Proxmox\RequestException as ConvoyRequestException;
use App\Models\Node;
use App\Services\Proxmox\Node\ProxmoxStatusClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Http\Client\ConnectionException;

class NodeStatusController
{
    /**
     * @throws NodeUnreachableException
     */
    public function __invoke(Node $node, ProxmoxStatusClient $client): NodeStatusData
    {
        try {
            return $client->setNode($node)->getStatus();
        } catch (ConvoyRequestException|GuzzleRequestException|ConnectionException $e) {
            // Letting this escape produced an anonymous 500, so the overview could
            // only say "live status is unavailable" -- true, useless, and the same
            // sentence whether the certificate was untrusted or the token was
            // wrong. Classify it the way the connection test already does and the
            // UI can name the cause and the fix.
            throw new NodeUnreachableException(
                ConnectionErrorCode::classify($e->getMessage()),
                previous: $e,
            );
        }
    }
}
