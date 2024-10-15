<?php

namespace App\Contracts\Repository;

use App\Exceptions\Repository\RecordNotFoundException;
use App\Models\Server;

interface ServerRepositoryInterface extends RepositoryInterface
{
    /**
     * Check if a given UUID and UUID-Short string are unique to a server.
     */
    public function isUniqueUuidCombo(string $uuid, string $short): bool;

    /**
     * Return a server by UUID.
     *
     * @throws RecordNotFoundException
     */
    public function getByUuid(string $uuid): Server;
}
