<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $session_id
 * @property int $user_id
 * @property ?string $ip_address
 * @property ?string $user_agent
 * @property Carbon $last_active_at
 */
class SessionRecord extends Model
{
    use Prunable;

    protected $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'user_agent',
        'last_active_at',
    ];

    /** The raw session id is a server-side secret; never serialize it. */
    protected $hidden = [
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** SessionRecords have no uuid; bind by primary key (the base Model defaults to uuid). */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Garbage-collect rows for sessions that can no longer exist: once a row hasn't been refreshed
     * for the full session lifetime, its Redis session has expired too, so the metadata is dead
     * weight. Read-time reconciliation in the controller handles listed sessions; this bounds table
     * growth for sessions that are never listed.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where(
            'last_active_at',
            '<',
            now()->subMinutes((int) config('session.lifetime')),
        );
    }
}
