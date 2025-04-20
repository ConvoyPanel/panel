<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class StorageToNode extends Model implements Sortable
{
    use SortableTrait;

    protected $table = 'storage_to_node';

    public $timestamps = false;

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }
}
