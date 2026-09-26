<?php

namespace App\Models;

use App\Enums\Admin\AdminPermission;
use App\Enums\Api\ApiKeyType;
use App\Enums\User\UserType;
use Eloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
 * @property string|null $avatar_path
 * @property UserType $type
 * @property ?int $admin_role_id
 * @property ?AdminRole $adminRole
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
        'type',
        'admin_role_id',
        // Not a column. See the root_admin attribute below: it reads and writes the Superadmin
        // role, so `create(['root_admin' => true])` still means what it always meant.
        'root_admin',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'email' => 'required|email|between:1,191|unique:users,email',
        'name' => 'required|string|between:1,191',
        'password' => ['sometimes', 'min:8', 'max:191', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/u', 'string'],
        'type' => 'sometimes|string|in:standard,guest',
        'admin_role_id' => 'sometimes|nullable|integer|exists:admin_roles,id',
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
     * The column default, mirrored in PHP.
     *
     * The database default only applies to the row; a freshly created model would still read
     * `type` as null until it was refreshed, and every payload built from it would fail on a
     * non-nullable enum.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => UserType::STANDARD->value,
    ];

    /** @see superadminRoleId() */
    private static ?int $superadminRoleId = null;

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
            'type' => UserType::class,
        ];
    }

    /**
     * Full administrator, expressed as the Superadmin role.
     *
     * The `root_admin` column is gone; this keeps the name working for the call sites that mean
     * "unrestricted admin" (Horizon's gate, staff-only fields in the audit payloads) and for the
     * provisioning paths that write it -- `p:make-user`, the seeders, the OAuth service and the
     * admin user form all still say `root_admin`, and still mean the same thing.
     *
     * Query builders cannot see this. Anything filtering or sorting on admin status goes through
     * `admin_role_id`.
     */
    protected function rootAdmin(): Attribute
    {
        return Attribute::make(
            // Compared against the cached id rather than loading the relation, so reading this in
            // a loop (an audit page maps it per row) stays one query for the whole request.
            get: fn (): bool => $this->admin_role_id !== null
                && $this->admin_role_id === self::superadminRoleId(),
            set: fn (mixed $value): array => [
                'admin_role_id' => filter_var($value, FILTER_VALIDATE_BOOL)
                    ? self::superadminRoleId()
                    : null,
            ],
        )->shouldCache();
    }

    /**
     * The Superadmin role's id, resolved once per process.
     *
     * Memoized because `root_admin` is read per row on listing payloads. {@see forgetRoleCache()}
     * clears it, which the test suite needs after a database refresh.
     */
    public static function superadminRoleId(): ?int
    {
        return self::$superadminRoleId ??= AdminRole::query()
            ->where('is_superadmin', '=', true)
            ->value('id');
    }

    public static function forgetRoleCache(): void
    {
        self::$superadminRoleId = null;
    }

    /**
     * @return BelongsTo<AdminRole, $this>
     */
    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
    }

    /** Whether this account can reach the admin area at all. */
    public function isAdmin(): bool
    {
        return $this->admin_role_id !== null;
    }

    public function isGuest(): bool
    {
        return $this->type === UserType::GUEST;
    }

    /**
     * Whether the account's role grants a specific admin permission.
     *
     * The role is loaded once and cached on the model, so repeated checks across a request cost
     * one query. A Superadmin answers true to everything, including permissions a later release
     * adds -- which is what makes migrating existing `root_admin` accounts onto it safe.
     */
    public function hasAdminPermission(AdminPermission $permission): bool
    {
        if ($this->admin_role_id === null) {
            return false;
        }

        $this->loadMissing('adminRole');

        return (bool) $this->adminRole?->grants($permission);
    }

    /** @return list<AdminPermission> */
    public function adminPermissions(): array
    {
        if ($this->admin_role_id === null) {
            return [];
        }

        $this->loadMissing('adminRole');

        return $this->adminRole?->grantedPermissions() ?? [];
    }

    /**
     * Servers shared with this account by their owners.
     *
     * @return HasMany<ServerSubuser, $this>
     */
    public function serverShares(): HasMany
    {
        return $this->hasMany(ServerSubuser::class);
    }

    /**
     * The outstanding invitation, if the account has never set a password.
     *
     * A HasOne rather than a lookup because the unique index on `user_invites.user_id` makes at
     * most one possible, and the sharing screen asks "have they accepted yet?" per row.
     *
     * @return HasOne<UserInvite, $this>
     */
    public function invite(): HasOne
    {
        return $this->hasOne(UserInvite::class);
    }

    /**
     * Where this account's picture is served from, or null when it has none.
     *
     * A path rather than a stored URL: the file lives on a swappable disk and
     * the panel is what serves it, so the address is derived at read time.
     */
    public function avatarUrl(): ?string
    {
        // `avatar_path` already carries the `avatars/` prefix the serving route
        // matches on, so it is the whole path -- not a segment to prepend to.
        return $this->avatar_path
            ? url("/{$this->avatar_path}")
            : null;
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
     * The account's own API keys, as opposed to every token whose `tokenable` happens to be this
     * row: an application token is minted by an admin against their own user but belongs to the
     * panel, and nothing on the account surface can see or revoke it.
     *
     * Constrained on the relation rather than at each call site so that route scope-binding
     * (`/users/{user}/api-keys/{apiKey}`) refuses an application token id with a 404.
     *
     * @return MorphMany<PersonalAccessToken, $this>
     */
    public function apiKeys(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable')
            ->where('type', '=', ApiKeyType::ACCOUNT->value);
    }

    /**
     * @return HasMany<Passkey, $this>
     */
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }

    /** A password login needs either supported second-factor method. */
    public function hasEnabledSecondFactor(): bool
    {
        return $this->hasEnabledTwoFactorAuthentication() || $this->passkeys()->exists();
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
