<?php

namespace App\Models;

use App\Data\Provisioner\ProvisionerFieldData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

/**
 * A script that runs inside a server on its first boot.
 *
 * Attached to images, in order. Servers built or reinstalled from such an image
 * get the scripts through cloud-init vendor data that Anchor writes to the
 * node (see docs/provisioner-anchor-contract.md).
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property ?string $description
 * @property string $script
 * @property array<int, array{key: string, label: string, required: bool}> $fields
 */
class Provisioner extends Model
{
    use HasFactory;

    /** Field keys become environment variable names, so they are kept to what one can hold. */
    public const FIELD_KEY_PATTERN = '/^[a-z][a-z0-9_]{0,31}$/';

    public const MAX_FIELDS = 20;

    protected $guarded = ['id'];

    public static array $validationRules = [
        'name' => 'required|string|between:1,40|unique:provisioners,name',
        'description' => 'nullable|string|max:191',
        // 64 KiB. Every script on an image travels in one snippet, and Anchor
        // refuses a snippet over 1 MiB.
        'script' => 'required|string|max:65536',
        'fields' => 'present|array',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<ImageDefinition, $this>
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(ImageDefinition::class, 'image_definition_provisioner')
            ->withPivot('position');
    }

    /**
     * @return Collection<int, ProvisionerFieldData>
     */
    public function fieldDefinitions(): Collection
    {
        return collect($this->fields ?? [])
            ->map(fn (array $field) => ProvisionerFieldData::from($field))
            ->values();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
