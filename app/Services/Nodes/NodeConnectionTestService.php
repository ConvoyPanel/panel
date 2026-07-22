<?php

namespace App\Services\Nodes;

use App\Data\Node\Testing\ConnectionResultData;
use App\Enums\Node\ConnectionErrorCode as Error;
use App\Exceptions\Proxmox\RequestException as ConvoyRequestException;
use App\Models\Node;
use App\Services\Proxmox\Node\ProxmoxStatusClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Http\Client\ConnectionException;

class NodeConnectionTestService
{
    public function __construct(
        private ProxmoxStatusClient $client,
    ) {}

    public function handle(Node $node): ConnectionResultData
    {
        try {
            $status = $this->client
                ->setNode($node)
                ->getStatus();
        } catch (ConvoyRequestException|GuzzleRequestException|ConnectionException $exception) {
            $message = $exception->getMessage();
            $errorType = Error::classify($message);
        }

        return new ConnectionResultData(
            success     : isset($status),
            errorMessage: $message ?? null,
            errorCode   : $errorType ?? null,
            data        : $status ?? null,
        );
    }
}
