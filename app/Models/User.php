<?php

namespace App\Models;

use App\Enums\Api\ApiKeyType;
use Eloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property bool $root_admin
 *
 * @mixin Eloquent
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, HasPasskeys
{
    use Authenticatable, Authorizable, HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'root_admin',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'email' => 'required|email|between:1,191|unique:users,email',
        'name' => 'required|string|between:1,191',
        'password' => ['sometimes', 'min:8', 'max:191', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/u', 'string'],
        'root_admin' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'root_admin' => 'boolean',
        ];
    }

    public function createToken(
        string $name,
        ApiKeyType $type,
        array $abilities = ['*'],
    ): NewAccessToken {
        $token = $this->tokens()->create([
            'type' => $type,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }

    /**
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * @return HasMany<Passkey, $this>
     */
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }

    public function getPassKeyName(): string
    {
        return $this->email;
    }

    public function getPassKeyId(): string
    {
        return $this->uuid;
    }

    public function getPassKeyDisplayName(): string
    {
        return $this->name;
    }

    /**
     * @return HasMany<SSHKey, $this>
     */
    public function sshKeys(): HasMany
    {
        return $this->hasMany(SSHKey::class);
    }

    /**
     * @return HasMany<OAuthConnection, $this>
     */
    public function oauthConnections(): HasMany
    {
        return $this->hasMany(OAuthConnection::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            $user->uuid = Str::uuid()->toString();
        });
    }
}
