<?php

namespace App\Http\Controllers\Admin;

use App\Data\Storage\StorageConsumerData;
use App\Data\Storage\StorageConsumersData;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\ISO;
use App\Models\ServerDisk;
use App\Models\Storage;
use Spatie\LaravelData\DataCollection;

/**
 * What is occupying a storage, so an operator can act on it rather than just
 * read a percentage.
 *
 * Sizes come straight off the models. All three carry `StorageSizeCast`, which
 * already returns bytes -- the MiB in the column is a storage detail, and
 * converting again here was how the first version reported everything as zero.
 *
 * Largest first in each group: the reason anyone opens this is to find what to
 * remove.
 */
class StorageConsumerController extends Controller
{
    public function __invoke(Storage $storage)
    {
        return new StorageConsumersData(
            servers: $this->servers($storage),
            backups: $this->backups($storage),
            isos: $this->isos($storage),
        );
    }

    /**
     * Servers by the space their disks take *on this storage*.
     *
     * Summed from `server_disks` rather than counted from `servers`, because a
     * server can keep its boot disk on one storage and a data disk on another --
     * listing the whole server against both would double-count it.
     */
    /**
     * @param  array<int, StorageConsumerData>  $rows
     * @return DataCollection<int, StorageConsumerData>
     */
    private function collect(array $rows): DataCollection
    {
        return StorageConsumerData::collect($rows, DataCollection::class)
            ->withoutWrapping();
    }

    /** @return DataCollection<int, StorageConsumerData> */
    private function servers(Storage $storage): DataCollection
    {
        $disks = ServerDisk::query()
            ->where('storage_id', $storage->id)
            ->with('server.user')
            ->get()
            ->groupBy('server_id');

        $rows = $disks
            ->map(function ($group) {
                /** @var ServerDisk $first */
                $first = $group->first();
                $server = $first->server;

                if ($server === null) {
                    return null;
                }

                return new StorageConsumerData(
                    id: $server->id,
                    name: $server->name,
                    size: (int) $group->sum('size'),
                    owner: $server->user?->email,
                    detail: 'vmid '.$server->vmid,
                    // Deleting a server is offered, but the dialog makes the
                    // operator type its name -- see the client.
                    deletable: true,
                );
            })
            ->filter()
            ->sortByDesc(fn (StorageConsumerData $row) => $row->size)
            ->values()
            ->all();

        return $this->collect($rows);
    }

    /** @return DataCollection<int, StorageConsumerData> */
    private function backups(Storage $storage): DataCollection
    {
        $rows = Backup::query()
            ->where('storage_id', $storage->id)
            ->with('server')
            ->get()
            ->map(fn (Backup $backup) => new StorageConsumerData(
                id: $backup->id,
                name: $backup->name,
                size: (int) ($backup->size ?? 0),
                owner: $backup->server?->name,
                detail: $backup->completed_at?->diffForHumans(),
                // A locked backup is locked for a reason; saying so on the row
                // beats offering a button that fails.
                deletable: ! $backup->is_locked,
            ))
            ->sortByDesc(fn (StorageConsumerData $row) => $row->size)
            ->values()
            ->all();

        return $this->collect($rows);
    }

    /** @return DataCollection<int, StorageConsumerData> */
    private function isos(Storage $storage): DataCollection
    {
        $rows = ISO::query()
            ->where('storage_id', $storage->id)
            ->get()
            ->map(fn (ISO $iso) => new StorageConsumerData(
                id: $iso->id,
                name: $iso->name,
                size: (int) ($iso->size ?? 0),
                owner: null,
                detail: $iso->file_name,
                deletable: true,
            ))
            ->sortByDesc(fn (StorageConsumerData $row) => $row->size)
            ->values()
            ->all();

        return $this->collect($rows);
    }
}
