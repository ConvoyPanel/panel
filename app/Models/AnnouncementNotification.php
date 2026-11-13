<?php

namespace Convoy\Models;

/**
 * The admins were emailed about an announcement. One row per announcement id,
 * so each is mailed once.
 */
class AnnouncementNotification extends Model
{
    protected $fillable = [
        'announcement_id',
        'recipients',
    ];

    protected $casts = [
        'recipients' => 'integer',
    ];

    public static array $validationRules = [
        'announcement_id' => 'required|string|max:120',
        'recipients' => 'integer|min:0',
    ];

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
