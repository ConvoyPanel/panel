<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class TemplateGroup extends Model implements Sortable
{
    use SortableTrait;

    public static array $validationRules = [
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

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function buildSortQuery(): Builder
    {
        return static::query()->where('node_id', $this->node_id);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
