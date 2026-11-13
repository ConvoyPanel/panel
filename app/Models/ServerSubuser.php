<?php

namespace App\Models;

use App\Enums\Server\ServerPermission;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * One person's access to one server they do not own.
 *
 * The owner has no row: they hold every permission implicitly, and storing that would mean
 * keeping it in step with the catalog forever. An empty `permissions` list is a real state --
 * the person can reach the server and do nothing on it.
 *
 * @property int $id
 * @property string $uuid
 * @property int $server_id
 * @property int $user_id
 * @property list<string> $permissions
 * @property ?int $created_by
 * @property Server $server
 * @property User $user
 *
 * @mixin Eloquent
 */
class ServerSubuser extends Model
{
    protected $table = 'server_subusers';

    protected $fillable = [
        'server_id',
        'user_id',
        'permissions',
        'created_by',
    ];

    public static array $validationRules = [
        'server_id' => 'required|integer|exists:servers,id',
        'user_id' => 'required|integer|exists:users,id',
        'permissions' => 'present|array',
        'permissions.*' => 'string',
        'created_by' => 'nullable|integer|exists:users,id',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    /** The permission rules with the catalog applied, for request validation. */
    public static function permissionRules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(ServerPermission::values())],
        ];
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function grants(ServerPermission $permission): bool
    {
        return in_array($permission->value, $this->permissions, true);
    }

    /** @return list<ServerPermission> */
    public function grantedPermissions(): array
    {
        return array_values(array_filter(
            array_map(fn (string $p) => ServerPermission::tryFrom($p), $this->permissions),
        ));
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $subuser) {
            $subuser->uuid ??= Str::uuid()->toString();
        });
    }
}
