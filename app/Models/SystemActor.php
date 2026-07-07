<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;

/**
 * A single, user-independent identity that represents the panel itself. Application (panel-wide)
 * API tokens are owned by this actor rather than by a user, so they survive deletion of the admin
 * who minted them. There is exactly one row.
 *
 * It uses HasApiTokens so Sanctum attaches the access token to the resolved actor
 * (`currentAccessToken()` / `tokenCan()` work), which the token-ability enforcement and
 * DenyApiTokenAccess middleware rely on.
 *
 * @property int $id
 * @property string $name
 */
class SystemActor extends Model
{
    use HasApiTokens;

    protected $guarded = ['id'];

    /** The one and only system actor. */
    public static function instance(): self
    {
        return static::query()->firstOrCreate([], ['name' => 'System']);
    }
}
