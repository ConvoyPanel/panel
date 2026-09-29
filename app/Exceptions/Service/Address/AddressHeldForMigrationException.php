<?php

namespace App\Exceptions\Service\Address;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * The address is the destination binding of a migration that has not finished.
 * Releasing it would let the allocator hand it to something else while a
 * migration is still on its way to it.
 */
class AddressHeldForMigrationException extends ConflictHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('This address is held for a server that is migrating. It frees itself when the migration finishes.');
    }

    public function errorCode(): string
    {
        return 'address_held_for_migration';
    }
}
