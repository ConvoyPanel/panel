<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Ramsey\Uuid\Uuid;

class Template extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    public static $validationRules = [
        'template_group_id' => 'required|integer|exists:template_groups,id',
        'name' => 'required|string|max:40',
        'vmid' => 'required|numeric|min:100|max:999999999',
        'hidden' => 'required|boolean',
    ];

    protected $guarded = [
        'id',
        'order_column',
        'created_at',
        'updated_at',
    ];

    public function group()
    {
        return $this->belongsTo(TemplateGroup::class, 'template_group_id');
    }

    public function buildSortQuery()
    {
        return static::query()->where('template_group_id', $this->template_group_id);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
