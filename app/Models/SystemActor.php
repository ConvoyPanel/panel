<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A single, user-independent identity that represents the panel itself. Application (panel-wide)
 * API tokens are owned by this actor rather than by a user, so they survive deletion of the admin
 * who minted them. There is exactly one row.
 *
 * It uses HasApiTokens so Sanctum attaches the access token to the resolved actor
 * (`currentAccessToken()` / `tokenCan()` work), which the token-ability enforcement and
 * DenyApiTokenAccess middleware rely on. It is Authorizable so the admin form requests' `can()`
 * checks reach the policies, which skip the role check for it but keep their other rules.
 *
 * @property int $id
 * @property string $name
 */
class SystemActor extends Model implements AuthorizableContract
{
    use Authorizable;
    use HasApiTokens;

    protected $guarded = ['id'];

    /** The one and only system actor. */
    public static function instance(): self
    {
        return static::query()->firstOrCreate([], ['name' => 'System']);
    }
}
