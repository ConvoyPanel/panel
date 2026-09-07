<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * How the OS picker groups what it offers -- "Ubuntu", "Windows Server".
 *
 * Purely a display grouping: it carries a name and an icon and owns no bytes
 * and no hardware. The definitions under it are the versions of that product an
 * operator has actually made available.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property ?string $description
 * @property ?string $icon
 * @property bool $is_admin_only
 */
class ImageGroup extends Model
{
    const UPDATED_AT = null;

    const CREATED_AT = null;

    public static array $validationRules = [
        'name' => 'required|string|max:40',
        'description' => 'nullable|string|max:1000',
        'is_admin_only' => 'sometimes|boolean',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_admin_only' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return HasMany<ImageDefinition, $this>
     */
    public function definitions(): HasMany
    {
        return $this->hasMany(ImageDefinition::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ImageGroup $model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
