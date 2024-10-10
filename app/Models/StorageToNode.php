<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageToNode extends Model
{
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
