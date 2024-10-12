<?php

namespace Convoy\Models;

use Convoy\Casts\StorageSizeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Snapshot extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $casts = [
        'size' => StorageSizeCast::class,
        'completed_at' => 'datetime',
    ];

    public static array $validationRules = [
        'server_id' => 'required|integer|exists:servers,id',
        'snapshot_id' => 'nullable|integer|exists:snapshots,id',
        'name' => 'required|string|max:40',
        'description' => 'nullable|string|max:191',
        'errors' => 'nullable|string|max:191',
        'is_locked' => 'sometimes|boolean',
        'size' => 'nullable|numeric|min:0',
        'completed_at' => 'nullable|date',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'snapshot_id')->with('children');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ISO $user) {
            $user->uuid = Str::uuid()->toString();
        });
    }
}
