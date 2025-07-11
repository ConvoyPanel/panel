<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

class TemplateGroup extends Model
{
    const UPDATED_AT = null;
    const CREATED_AT = null;

    public static array $validationRules = [
        'name' => 'required|string|max:40',
        'description' => 'nullable|string|max:1000',
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

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
