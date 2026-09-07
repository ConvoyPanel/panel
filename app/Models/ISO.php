<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * An ISO the panel offers, independent of any node.
 *
 * It used to be a file on one storage: the same disc on four nodes was four
 * rows, and a node added later simply did not have it. Now the row is the ISO
 * -- a name, a source and a hash -- and getting it onto a node is something
 * that happens when a user mounts it, not something an admin arranges first.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property ?string $url A source the operator hosts
 * @property ?string $path A file uploaded to the panel, on the artifacts disk
 * @property ?string $sha256
 * @property string $file_name The name this takes on a node's ISO storage
 * @property ?int $size
 * @property bool $hidden
 */
class ISO extends Model
{
    use HasFactory;

    protected $table = 'iso_library';

    protected $guarded = ['id', 'created_at'];

    public const UPDATED_AT = null;

    public static array $validationRules = [
        'name' => 'required|string|min:1|max:40',
        'url' => 'nullable|url|required_without:path',
        'path' => 'nullable|string|required_without:url',
        'sha256' => 'nullable|string|size:64',
        'file_name' => 'required|string|ends_with:.iso|max:191',
        'size' => 'sometimes|numeric|min:0',
        'hidden' => 'sometimes|boolean',
    ];

    protected function casts(): array
    {
        return [
            'size' => StorageSizeCast::class,
            'hidden' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** Whether the panel is the one serving this file. */
    public function isHosted(): bool
    {
        return filled($this->path);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ISO $iso) {
            $iso->uuid = Str::uuid()->toString();
        });
    }
}
