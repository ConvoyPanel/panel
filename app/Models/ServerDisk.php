<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One VM disk. A server has exactly one primary (OS/boot) disk plus zero or
 * more secondary data disks, each potentially on a different storage.
 *
 * @property int $id
 * @property int $server_id
 * @property int $storage_id
 * @property int $size
 * @property ?string $interface
 * @property bool $is_primary
 * @property int $disk_index
 * @property Server $server
 * @property Storage $storage
 */
class ServerDisk extends Model
{
    use HasFactory;

    protected $table = 'server_disks';

    public $timestamps = false;

    protected $guarded = [
        'id',
    ];

    /**
     * Disks are addressed by their numeric id in routes (they have no uuid,
     * unlike the base Model's default).
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected function casts(): array
    {
        return [
            'size' => StorageSizeCast::class,
            'is_primary' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Storage, $this>
     */
    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }
}
