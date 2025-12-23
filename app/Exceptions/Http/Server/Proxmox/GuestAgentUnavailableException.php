<?php

namespace App\Exceptions\Http\Server\Proxmox;

use App\Exceptions\DisplayException;
use Illuminate\Http\Response;
use Throwable;

class GuestAgentUnavailableException extends DisplayException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, $previous, Response::HTTP_CONFLICT);
    }
}

