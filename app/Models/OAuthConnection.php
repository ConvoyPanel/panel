<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $name
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $last_used_at
 */
class OAuthConnection extends Model
{
    protected $table = 'oauth_connections';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'name',
        'email',
        'last_used_at',
    ];

    public static array $validationRules = [
        'user_id' => 'required|integer|exists:users,id',
        'provider' => 'required|string|max:191',
        'provider_id' => 'required|string|max:191',
        'name' => 'nullable|string|max:191',
        'email' => 'nullable|string|max:191',
        'last_used_at' => 'nullable|date',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** OAuth connections have no uuid; bind by primary key (the base Model defaults to uuid). */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
