<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Performave\EloquentSortable\Sortable;
use Performave\EloquentSortable\SortableTrait;

class TemplateGroup extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    public static $validationRules = [
        'node_id' => 'required|integer|exists:nodes,id',
        'uuid' => 'required|uuid',
        'name' => 'required|string|max:40',
        'hidden' => 'sometimes|boolean',
    ];

    protected $guarded = [
        'id',
        'order_column',
        'created_at',
        'updated_at',
    ];

    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    public function buildSortQuery()
    {
        return static::query()->where('node_id', $this->node_id);
    }
}
