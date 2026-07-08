<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $session_id
 * @property int $user_id
 * @property ?string $ip_address
 * @property ?string $user_agent
 * @property \Illuminate\Support\Carbon $last_active_at
 */
class SessionRecord extends Model
{
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
}
