<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $server_id
 * @property int $storage_id
 * @property bool $is_locked
 * @property string $name
 * @property ?string $file_name
 * @property ?int $size
 * @property ?string $errors
 * @property Server $server
 * @property Storage $storage
 */
class Backup extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static array $validationRules = [
        'uuid' => 'required|uuid',
        'server_id' => 'required|integer|exists:servers,id',
        'storage_id' => 'required|integer|exists:storages,id',
        'is_locked' => 'sometimes|boolean',
        'name' => 'required|string|min:1|max:40',
        'file_name' => 'nullable|string',
        'size' => 'nullable|numeric|min:0',
        'completed_at' => 'nullable|date',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'size' => StorageSizeCast::class,
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Storage, $this>
     */
    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    public function scopeSuccessful(Builder $query): void
    {
        $query->whereNull('errors')
            ->whereNotNull('completed_at');
    }

    public function scopeRunning(Builder $query): void
    {
        $query->whereNull('completed_at');
    }
}
