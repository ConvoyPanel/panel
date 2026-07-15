<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class StorageToNode extends Model implements Sortable
{
    use SortableTrait;

    protected $table = 'storage_to_node';

    /**
     * `storage_to_node` is a composite pivot with NO `id` column. Left to its
     * defaults Eloquent assumes an auto-incrementing `id`, and Postgres then
     * rejects every insert with `column "id" does not exist` on the appended
     * `returning "id"` — which also broke `save()` in the update path. MySQL
     * tolerated it, so this only surfaced after the move to Postgres, and only
     * against a live node (a seeder writes the pivot directly).
     *
     * `storage_id` matches the key column `updateBackupOrder()` already passes
     * to `setNewOrder()`.
     */
    protected $primaryKey = 'storage_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'storage_id',
        'node_id',
        'backup_order',
    ];

    public array $sortable = [
        'order_column_name' => 'backup_order',
        'sort_when_creating' => true,
    ];

    public function buildSortQuery(): Builder
    {
        // Add a condition using `whereHas`. This ensures that we only include
        // StorageToNode records where the related 'storage' model exists AND
        // meets the condition specified in the closure.
        return static::query()->whereHas('storage', function (Builder $query) {
            $query->where('stores_backups', true);
        });
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }
}
