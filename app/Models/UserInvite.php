<?php

namespace App\Models;

use App\Services\Users\UserInviteService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A pending invitation for an account that has never had a password of its own.
 *
 * The row holds only the hash of the link's token, so this model can tell you an invite exists
 * and when it lapses, but never what to send — that string is returned once, by
 * {@see UserInviteService::issue()}, and is unrecoverable afterwards.
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property User $user
 */
class UserInvite extends Model
{
    protected $table = 'user_invites';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'token' => 'required|string|size:64',
        'expires_at' => 'required|date',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Expired invites are excluded at the query rather than filtered afterwards, so a lapsed
     * link is indistinguishable from one that never existed — the consume endpoint should not
     * be able to tell an attacker which of the two they are holding.
     */
    public function scopeUnexpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', CarbonImmutable::now());
    }
}
