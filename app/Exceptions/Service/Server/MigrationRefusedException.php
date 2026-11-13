<?php

namespace App\Exceptions\Service\Server;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * The destination was re-checked at commit time and is no longer acceptable.
 *
 * The dialog computes a verdict, but the operator can sit on it: a pool can be
 * detached, a bridge renamed, or the last free address taken in between. The
 * verdict is therefore recomputed when the request lands, and this is what a
 * disagreement looks like.
 */
class MigrationRefusedException extends ConflictHttpException implements HasErrorCode
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }

    public function errorCode(): string
    {
        return 'migration_refused';
    }
}
