<?php

namespace Convoy\Repositories\Eloquent;

use Convoy\Contracts\Repository\ServerRepositoryInterface;
use Convoy\Exceptions\Repository\RecordNotFoundException;
use Convoy\Models\Server;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServerRepository extends EloquentRepository implements ServerRepositoryInterface
{
    /**
     * @return string
     */
    public function model()
    {
        return Server::class;
    }

    /**
     * Check if a given UUID and UUID-Short string are unique to a server.
     */
    public function isUniqueUuidCombo(string $uuid, string $short): bool
    {
        return ! $this->getBuilder()->where('uuid', '=', $uuid)->orWhere('uuid_short', '=', $short)->exists();
    }

    /**
     * Return a server by UUID.
     *
     * @throws \Convoy\Exceptions\Repository\RecordNotFoundException
     */
    public function getByUuid(string $uuid): Server
    {
        try {
            /** @var \Convoy\Models\Server $model */
            $model = $this->getBuilder()
                ->where(function (Builder $query) use ($uuid) {
                    $query->where('uuid_short', $uuid)->orWhere('uuid', $uuid);
                })
                ->firstOrFail($this->getColumns());

            return $model;
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }
    }
}
