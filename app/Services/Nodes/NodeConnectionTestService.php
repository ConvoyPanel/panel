<?php

namespace App\Services\Nodes;

use App\Data\Node\Testing\ConnectionResultData;
use App\Enums\Node\Testing\ConnectionErrorCode as Error;
use App\Exceptions\Repository\Proxmox\RequestException as ConvoyRequestException;
use App\Models\Node;
use App\Repositories\Proxmox\Node\ProxmoxStatusRepository;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Http\Client\ConnectionException;

class NodeConnectionTestService
{
    public function __construct(
        private ProxmoxStatusRepository $repository,
    ) {}

    public function handle(Node $node): ConnectionResultData
    {
        try {
            $status = $this->repository
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
