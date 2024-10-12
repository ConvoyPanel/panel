<?php

namespace Convoy\Contracts\Repository;

use Convoy\Exceptions\Repository\RecordNotFoundException;
use Convoy\Models\Server;

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
