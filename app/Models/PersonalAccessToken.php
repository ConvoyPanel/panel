<?php

namespace App\Models;

use App\Enums\Api\ApiKeyType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * @property ApiKeyType $type
 * @property ?int $created_by
 * @property ?array<int, string> $allowed_networks
 * @property ?User $createdBy
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'type',
        'name',
        'token',
        'abilities',
        'allowed_networks',
        'created_by',
    ];

    /**
     * The admin who minted this token. Kept for audit; nulled (not cascaded) if that user is
     * deleted, so an application token outlives its creator.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'type' => ApiKeyType::class,
            'abilities' => 'json',
            'allowed_networks' => 'array',
            'last_used_at' => 'datetime',
        ];
    }
}
