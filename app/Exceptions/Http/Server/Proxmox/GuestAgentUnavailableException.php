<?php

namespace App\Exceptions\Http\Server\Proxmox;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class GuestAgentUnavailableException extends ConflictHttpException implements HasErrorCode
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }

    public function errorCode(): string
    {
        return 'guest_agent_unavailable';
    }
}
