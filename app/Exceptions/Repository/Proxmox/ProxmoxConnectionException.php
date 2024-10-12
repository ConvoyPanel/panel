<?php

namespace App\Exceptions\Repository\Proxmox;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;
use App\Exceptions\Repository\RepositoryException;

class ProxmoxConnectionException extends RepositoryException
{
    public function __construct(Response $response, RequestException $exception)
    {
        parent::__construct($response->reason() . PHP_EOL . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString(), $exception->getCode(), $exception);
    }
}
