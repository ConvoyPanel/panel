<?php

namespace Convoy\Exceptions\Repository\Proxmox;

use Convoy\Exceptions\Repository\RepositoryException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class ProxmoxConnectionException extends RepositoryException
{
//    public function __construct(RequestException $previous)
//    {
//        /* @var Response $previous->response */
//
//        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
//    }
}
