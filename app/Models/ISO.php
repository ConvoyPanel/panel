<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $storage_id
 * @property bool $is_successful
 * @property string $name
 * @property ?string $file_name
 * @property ?int $size
 * @property bool $hidden
 * @property ?CarbonImmutable $completed_at
 * @property Storage $storage
 */
class ISO extends Model
{
    use HasFactory;

    protected $table = 'iso_library';

    protected $guarded = ['id', 'created_at'];

    public const UPDATED_AT = null;

    public static array $validationRules = [
        'storage_id' => 'required|integer|exists:storages,id',
        'is_successful' => 'sometimes|boolean',
        'name' => 'required|string|min:1|max:40',
        'file_name' => 'required|string|ends_with:.iso|max:191',
        'size' => 'sometimes|numeric|min:0',
        'hidden' => 'sometimes|boolean',
        'completed_at' => 'nullable|date',
    ];

    protected function casts(): array
    {
        return [
            'is_successful' => 'boolean',
            'size' => StorageSizeCast::class,
            'hidden' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Storage, $this>
     */
    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ISO $user) {
            $user->uuid = Str::uuid()->toString();
        });
    }
}
