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
 * @property ?string $registry_slug
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
     * Names the relation Laravel uses to look up a definition from the URL, as in
     * `/image-groups/{image_group}/images/{image_definition}`.
     *
     * The lookup goes through the group, so a definition that belongs to another
     * group is a 404. Laravel finds the relation by pluralising the parameter
     * name, which would be `imageDefinitions()`. Ours is `definitions()`, so
     * without this the route fails with a 500.
     */
    protected function childRouteBindingRelationshipName($childType): string
    {
        return $childType === 'image_definition'
            ? 'definitions'
            : parent::childRouteBindingRelationshipName($childType);
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
