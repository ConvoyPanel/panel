<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An admin hid an announcement from their overview. Keyed by the feed's id, so
 * an edited announcement stays dismissed.
 */
class AnnouncementDismissal extends Model
{
    protected $fillable = [
        'user_id',
        'announcement_id',
    ];

    public static array $validationRules = [
        'user_id' => 'required|integer|exists:users,id',
        'announcement_id' => 'required|string|max:120',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
