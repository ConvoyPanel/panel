<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * One (storage definition, node) link: this node mounts that storage.
 *
 * Besides availability it carries the per-node facts -- what the node last
 * saw in the store, and where the store sits in the node's backup order. For
 * a shared pool every link's figures agree; for a local definition each link
 * describes a physically different disk, which is exactly why the figures
 * live here and not on the definition.
 *
 * A real `Pivot` so `Storage::nodes()`/`Node::storages()` can hydrate rows
 * through this class and callers get real casts on `pivot` instead of raw
 * strings. (Not the app's validating base model -- this class has no rules,
 * and Pivot is what `using()` requires.)
 *
 * @property int $storage_id
 * @property int $node_id
 * @property ?int $backup_order
 * @property ?int $discovered_total
 * @property ?int $discovered_used
 * @property ?CarbonImmutable $discovered_at
 */
class StorageToNode extends Pivot implements Sortable
{
    use SortableTrait;

    protected $table = 'storage_to_node';

    /**
     * `storage_to_node` is a composite pivot with NO `id` column, so nothing
     * may ever address a row by single key. `$incrementing = false` stops
     * Postgres inserts from failing on an appended `returning "id"`, and the
     * composite (storage, node) addressing comes from Pivot's `AsPivot` --
     * which only engages when the model's key attribute is absent. That is
     * why there is deliberately no `$primaryKey` override here: pointing it
     * at `storage_id` made `AsPivot` fall back to whole-key deletes, and
     * detaching one node's link silently severed the storage from every
     * other node too.
     */
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'storage_id',
        'node_id',
        'backup_order',
        'discovered_total',
        'discovered_used',
        'discovered_at',
    ];

    protected function casts(): array
    {
        return [
            'discovered_at' => 'immutable_datetime',
        ];
    }

    public array $sortable = [
        'order_column_name' => 'backup_order',
        'sort_when_creating' => true,
    ];

    /**
     * The set this row is ordered within: the backup storages of its own node.
     *
     * Scoped by `node_id` as well as by content type. Without it the sequence is
     * global, so the next order number for a storage on one node is chosen by
     * looking at every node's storages -- and a shared pool would share one
     * position across all of them, which is not what backup order means.
     */
    public function buildSortQuery(): Builder
    {
        return static::query()
            ->where('node_id', $this->node_id)
            ->whereHas('storage', function (Builder $query) {
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
