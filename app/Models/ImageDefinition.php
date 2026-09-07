<?php

namespace App\Models;

use App\Services\Images\OsProfiles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * A type of image -- "Ubuntu 24.04" -- and everything needed to boot one.
 *
 * It holds no bytes. What it holds is the half of a Proxmox template that a
 * clone used to supply for free: the `ostype` every cloud-init decision branches
 * on, and the hardware keys that decide whether the guest finds its root disk at
 * all. Getting `scsihw` wrong here is `0x7B INACCESSIBLE_BOOT_DEVICE` on Windows,
 * which is why `hardware` is a merge over a per-OS default rather than a form an
 * admin has to complete: an unset key inherits, it does not clear.
 *
 * @property int $id
 * @property string $uuid
 * @property int $image_group_id
 * @property string $name
 * @property ?string $description
 * @property bool $is_admin_only
 * @property string $ostype
 * @property array $hardware
 * @property ?int $minimum_cores
 * @property ?int $minimum_memory
 * @property ImageGroup $group
 */
class ImageDefinition extends Model
{
    public static array $validationRules = [
        'image_group_id' => 'required|integer|exists:image_groups,id',
        'name' => 'required|string|max:40',
        'description' => 'nullable|string|max:1000',
        'is_admin_only' => 'sometimes|boolean',
        'ostype' => 'required|string|max:20',
        'hardware' => 'sometimes|array',
        'minimum_cores' => 'nullable|integer|min:1',
        'minimum_memory' => 'nullable|integer|min:1',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_admin_only' => 'boolean',
            'hardware' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<ImageGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ImageGroup::class, 'image_group_id');
    }

    /**
     * @return HasMany<ImageVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ImageVersion::class);
    }

    /**
     * The newest active version, which is what a new server gets.
     *
     * Ordered by the integer triple rather than the string so 1.10.0 beats
     * 1.9.0. Retired versions stay queryable because servers already built from
     * them still point at them; they are just never handed out again.
     */
    public function latestVersion(): ?ImageVersion
    {
        return $this->versions()
            ->where('is_active', true)
            ->orderByDesc('version_major')
            ->orderByDesc('version_minor')
            ->orderByDesc('version_patch')
            ->first();
    }

    /**
     * The hardware this definition actually provisions with.
     *
     * The stored `hardware` is an overlay, not a specification: whatever it does
     * not name comes from the `ostype`'s default. That is what lets the admin
     * form ask one question and still produce a complete `qm create` call.
     */
    public function effectiveHardware(): array
    {
        return OsProfiles::merge($this->ostype, $this->hardware ?? []);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ImageDefinition $model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }
}
