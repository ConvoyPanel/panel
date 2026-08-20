<?php

namespace App\Models;

use App\Enums\Anchor\AnchorMode;
use App\Enums\Anchor\EnrollmentKeyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A one-time or reusable credential that admits a new Anchor installation.
 *
 * Contrast {@see Anchor::$enrollment_token_hash}, which re-keys an installation
 * the panel already has a row for. This one is bound to nothing until a machine
 * presents it.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $token_hash
 * @property AnchorMode|null $mode
 * @property int|null $max_uses
 * @property int $uses
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $last_used_at
 * @property int|null $created_by
 * @property ?User $createdBy
 */
class AnchorEnrollmentKey extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * The hash is a verifier, not a secret to hand back, but it is also the
     * only thing standing between a leaked API response and a usable key.
     */
    protected $hidden = ['token_hash'];

    public static array $validationRules = [
        'uuid' => 'required|uuid',
        'name' => 'required|string|max:191',
        'token_hash' => 'required|string|size:64',
        'mode' => 'nullable|string|in:agent,relay',
        'max_uses' => 'nullable|integer|min:1',
        'uses' => 'required|integer|min:0',
        'expires_at' => 'nullable|date',
        'revoked_at' => 'nullable|date',
        'last_used_at' => 'nullable|date',
        'created_by' => 'nullable|integer|exists:users,id',
    ];

    protected function casts(): array
    {
        return [
            'mode' => AnchorMode::class,
            'max_uses' => 'integer',
            'uses' => 'integer',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function status(): EnrollmentKeyStatus
    {
        return match (true) {
            $this->revoked_at !== null => EnrollmentKeyStatus::REVOKED,
            $this->expires_at?->isPast() === true => EnrollmentKeyStatus::EXPIRED,
            $this->max_uses !== null && $this->uses >= $this->max_uses => EnrollmentKeyStatus::EXHAUSTED,
            default => EnrollmentKeyStatus::ACTIVE,
        };
    }

    public function isUsable(): bool
    {
        return $this->status() === EnrollmentKeyStatus::ACTIVE;
    }

    /**
     * Whether this key permits an installation claiming `$mode`.
     *
     * A null `mode` on the key means "either", which is why this cannot be
     * written as a plain equality check at the call site.
     */
    public function permits(AnchorMode $mode): bool
    {
        return $this->mode === null || $this->mode === $mode;
    }

    /**
     * The same question {@see status()} answers, pushed into SQL so a roster
     * can filter without hydrating every row.
     *
     * Kept beside `status()` rather than in a query-builder class precisely
     * because the two must agree; separating them is how they drift.
     *
     * @param  Builder<AnchorEnrollmentKey>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->whereNull('revoked_at')
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->where(fn (Builder $query) => $query
                ->whereNull('max_uses')
                ->orWhereColumn('uses', '<', 'max_uses'));
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
