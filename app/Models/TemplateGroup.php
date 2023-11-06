<?php

namespace Convoy\Models;

use Ramsey\Uuid\Uuid;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateGroup extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    public static $validationRules = [
        'node_id' => 'required|integer|exists:nodes,id',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
