<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use App\Data\Image\ImageDiskData;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

/**
 * One concrete build of a definition: the disks, and their hashes.
 *
 * Versions are replaced rather than edited. A monthly rebuild is a new row, so a
 * node can keep serving an older build to servers that reference it, staleness
 * is answerable without asking a node anything (it either holds these hashes or
 * it does not), and "this image was rebuilt" stops being a destructive edit.
 *
 * @property int $id
 * @property string $uuid
 * @property int $image_definition_id
 * @property string $version
 * @property int $version_major
 * @property int $version_minor
 * @property int $version_patch
 * @property array $disks
 * @property int $size Bytes. Mebibytes on disk -- see StorageSizeCast.
 * @property bool $is_active
 * @property ImageDefinition $definition
 */
class ImageVersion extends Model
{
    public static array $validationRules = [
        'image_definition_id' => 'required|integer|exists:image_definitions,id',
        'version' => 'required|string|max:32|regex:/^\d+\.\d+\.\d+$/',
        'disks' => 'required|array|min:1',
        'is_active' => 'sometimes|boolean',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'disks' => 'array',
            // Same convention as every other size column: MiB stored, bytes read.
            'size' => StorageSizeCast::class,
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<ImageDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ImageDefinition::class, 'image_definition_id');
    }

    /**
     * Servers built from this version. Guards deletion: a version is a server's
     * only record of what it was made from.
     *
     * @return HasMany<Deployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * @return Collection<int, ImageDiskData>
     */
    public function diskSet(): Collection
    {
        return collect($this->disks ?? [])->map(fn (array $disk) => ImageDiskData::from($disk));
    }

    public function systemDisk(): ?ImageDiskData
    {
        return $this->diskSet()->firstWhere(fn (ImageDiskData $disk) => $disk->isSystem());
    }

    /**
     * The smallest plan this version can be provisioned onto.
     *
     * An imported disk inherits the source's virtual size and `qm disk resize`
     * only grows, so the system disk's provisioned size is a hard floor rather
     * than a suggestion.
     */
    public function minimumDiskSize(): int
    {
        return $this->systemDisk()?->virtualSize ?? 0;
    }

    /**
     * Keep the derived columns in step with what an operator actually set.
     *
     * Hung off `creating` and `updating` rather than `saving`, which is not a
     * style choice: {@see Model::boot} registers a `saving`
     * listener that returns `true`, and model events halt on the first non-null
     * return -- so a `saving` listener added afterwards never runs at all. It
     * fails silently, which is how the version triple below sat at 0/0/0 and
     * made `latestVersion()` return an arbitrary row.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ImageVersion $model) {
            $model->uuid = Uuid::uuid4()->toString();
            self::syncDerivedColumns($model);
        });

        static::updating(function (ImageVersion $model) {
            self::syncDerivedColumns($model);
        });
    }

    private static function syncDerivedColumns(ImageVersion $model): void
    {
        // Ordered by the integer triple rather than the string, so 1.10.0 beats
        // 1.9.0. Derived here so nobody has to remember four fields.
        if ($model->isDirty('version')) {
            [$major, $minor, $patch] = array_pad(
                array_map('intval', explode('.', (string) $model->version)),
                3,
                0,
            );

            $model->version_major = $major;
            $model->version_minor = $minor;
            $model->version_patch = $patch;
        }

        if ($model->isDirty('disks')) {
            // The disks JSON is in bytes -- a cast cannot reach inside a JSON
            // column -- and the cast on this attribute scales the sum down to
            // the mebibytes the column stores.
            $model->size = collect($model->disks ?? [])->sum(fn (array $disk) => (int) ($disk['size'] ?? 0));
        }
    }
}
