<?php

namespace App\Models;

use App\Enums\Admin\AdminPermission;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * A named set of admin permissions.
 *
 * An account holds zero or one of these. One role per account rather than a union of several,
 * because the question an operator asks is "what can this person do?" and with one role the
 * answer is its name -- legible in a table column, on the user page and in the audit log. The
 * combination a union would buy is covered by cloning a role and editing it, which the UI offers
 * as a first-class action.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property ?string $description
 * @property list<string> $permissions
 * @property bool $is_system
 * @property bool $is_superadmin
 *
 * @mixin Eloquent
 */
class AdminRole extends Model
{
    /** The role every pre-existing `root_admin` account is migrated onto. */
    public const SUPERADMIN = 'Superadmin';

    /**
     * The column defaults, mirrored in PHP so a freshly created role reads them without a refresh.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_system' => false,
        'is_superadmin' => false,
    ];

    protected $fillable = [
        'name',
        'description',
        'permissions',
    ];

    public static array $validationRules = [
        'name' => 'required|string|between:1,191|unique:admin_roles,name',
        'description' => 'nullable|string|max:191',
        'permissions' => 'present|array',
        'permissions.*' => 'string',
        'is_system' => 'boolean',
        'is_superadmin' => 'boolean',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
            'is_superadmin' => 'boolean',
        ];
    }

    public static function rulesWithCatalog(): array
    {
        $rules = self::getRules();
        $rules['permissions.*'] = ['string', Rule::in(AdminPermission::values())];

        return $rules;
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Whether this role satisfies the given requirement.
     *
     * A superadmin answers true to everything, including permissions added by a later release --
     * that is what makes the upgrade path safe for the accounts migrated onto it.
     */
    public function grants(AdminPermission $permission): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        foreach ($this->permissions as $held) {
            if (AdminPermission::tryFrom($held)?->satisfies($permission)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<AdminPermission> */
    public function grantedPermissions(): array
    {
        return $this->is_superadmin
            ? AdminPermission::cases()
            : array_values(array_filter(
                array_map(fn (string $p) => AdminPermission::tryFrom($p), $this->permissions),
            ));
    }

    public static function superadmin(): self
    {
        return self::query()->where('is_superadmin', '=', true)->firstOrFail();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $role) {
            $role->uuid ??= Str::uuid()->toString();
        });
    }
}
