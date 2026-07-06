<?php

namespace App\Exceptions\Http\Server;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class PowerActionInProgressException extends ConflictHttpException implements HasErrorCode
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            'A power action is already in progress for this server, please wait for it to finish.',
            $previous,
        );
    }

    public function errorCode(): string
    {
        return 'power_action_in_progress';
    }
}
