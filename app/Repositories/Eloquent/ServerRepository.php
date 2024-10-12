<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repository\ServerRepositoryInterface;
use App\Exceptions\Repository\RecordNotFoundException;
use App\Models\Server;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ServerRepository extends EloquentRepository implements ServerRepositoryInterface
{
    public function model(): string
    {
        return Server::class;
    }

    public function isUniqueVmId(int $nodeId, int $vmid): bool
    {
        return !$this->getBuilder()
                     ->where('vmid', '=', $vmid)
                     ->where('node_id', '=', $nodeId)
                     ->exists();
    }

    /**
     * Check if a given UUID and UUID-Short string are unique to a server.
     */
    public function isUniqueUuidCombo(string $uuid, string $short): bool
    {
        return !$this->getBuilder()->where('uuid', '=', $uuid)->orWhere('uuid_short', '=', $short)
                     ->exists();
    }

    /**
     * Return a server by UUID.
     *
     * @throws RecordNotFoundException
     */
    public function getByUuid(string $uuid): Server
    {
        try {
            /** @var Server $model */
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
