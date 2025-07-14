<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class Template extends Model
{
    const UPDATED_AT = null;
    const CREATED_AT = null;

    public static array $validationRules = [
        'template_group_id' => 'required|integer|exists:template_groups,id',
        'name' => 'required|string|max:40',
        'description' => 'nullable|string|max:1000',
        'vmid' => 'required|numeric|min:100|max:999999999',
        'is_admin_only' => 'sometimes|boolean',
    ];

    protected $guarded = [
        'id',
    ];

    protected function casts(): array
    {
        return [
            'is_admin_only' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TemplateGroup::class, 'template_group_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
