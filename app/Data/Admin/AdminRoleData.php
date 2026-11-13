<?php

namespace App\Data\Admin;

use App\Enums\Admin\AdminPermission;
use App\Models\AdminRole;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapInputName(SnakeCaseMapper::class)]
class AdminRoleData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public ?string $description,
        /**
         * What the role grants. A superadmin's list is the whole catalog rather than an empty
         * array, so the screen reads the same for every role instead of special-casing one.
         *
         * @var list<AdminPermission>
         */
        public array $permissions,
        /** Shipped with the panel: renameable, but its permissions are fixed and it cannot be deleted. */
        public bool $isSystem,
        public bool $isSuperadmin,
        public int|Optional $usersCount,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(AdminRole $role): self
    {
        return new self(
            id: $role->id,
            uuid: $role->uuid,
            name: $role->name,
            description: $role->description,
            permissions: $role->grantedPermissions(),
            isSystem: $role->is_system,
            isSuperadmin: $role->is_superadmin,
            usersCount: isset($role->users_count) ? (int) $role->users_count : Optional::create(),
            createdAt: CarbonImmutable::parse($role->created_at),
        );
    }
}
