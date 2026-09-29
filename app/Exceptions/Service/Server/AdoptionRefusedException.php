<?php

namespace App\Exceptions\Service\Server;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * The guest cannot be adopted at all: it is a template, it is locked mid-task,
 * its vmid is already held, or its disk is on a storage Convoy has not
 * discovered. Distinct from an address the panel could not resolve, which never
 * stops an adoption.
 */
class AdoptionRefusedException extends ConflictHttpException implements HasErrorCode
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }

    public function errorCode(): string
    {
        return 'adoption_refused';
    }
}
