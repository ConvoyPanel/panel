<?php

use App\Enums\Admin\AdminPermission;
use App\Models\AdminRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Replaces the single `users.root_admin` bit with a named role carrying a set of permissions.
 *
 * Existing admins are migrated onto the **Superadmin** role, which holds everything, so nobody
 * loses access at upgrade time and no operator has to do anything. Narrowing an account is then
 * a deliberate act: open the user and change the role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name')->unique();
            $table->string('description')->nullable();

            // The permission values, as a json list. A pivot table would let a permission be
            // renamed by id, but the catalog is a PHP enum whose values are the stable API --
            // there is no second source of truth for rows to point at.
            $table->json('permissions');

            // A shipped role. Renameable and describable, but its permissions are fixed and it
            // cannot be deleted, so an install always has something to fall back to.
            $table->boolean('is_system')->default(false);

            // Answers `true` to every permission, including ones added by a later release.
            // Exactly one row carries it.
            $table->boolean('is_superadmin')->default(false);

            $table->timestamps();
        });

        $this->seedSystemRoles();

        Schema::table('users', function (Blueprint $table) {
            // Null means "not an admin at all", which is what the overwhelming majority of rows
            // are. Nulled rather than cascaded on role delete: a deleted role must not take the
            // accounts that held it with it.
            $table->foreignId('admin_role_id')
                ->nullable()
                ->after('root_admin')
                ->constrained('admin_roles')
                ->nullOnDelete();
        });

        DB::table('users')
            ->where('root_admin', '=', true)
            ->update(['admin_role_id' => $this->superadminId()]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('root_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('root_admin')->default(false)->after('email');
        });

        DB::table('users')
            ->whereIn('admin_role_id', DB::table('admin_roles')->where('is_superadmin', '=', true)->pluck('id'))
            ->update(['root_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_role_id');
        });

        Schema::dropIfExists('admin_roles');
    }

    private function superadminId(): int
    {
        return (int) DB::table('admin_roles')->where('is_superadmin', '=', true)->value('id');
    }

    /**
     * The four roles an install ships with. Anything else is assembled from the same catalog.
     */
    private function seedSystemRoles(): void
    {
        $now = now();

        $roles = [
            [
                'name' => AdminRole::SUPERADMIN,
                'description' => 'Full access to every part of the admin area.',
                'permissions' => [],
                'is_superadmin' => true,
            ],
            [
                'name' => 'Support',
                'description' => 'Reads the fleet and restarts a customer server, without changing anything.',
                'permissions' => [
                    AdminPermission::OVERVIEW_READ,
                    AdminPermission::AUDIT_LOGS_READ,
                    AdminPermission::LOCATIONS_READ,
                    AdminPermission::NODES_READ,
                    AdminPermission::SERVERS_READ,
                    AdminPermission::SERVERS_POWER,
                    AdminPermission::USERS_READ,
                    AdminPermission::ISOS_READ,
                    AdminPermission::IMAGE_GROUPS_READ,
                    AdminPermission::ADDRESS_BLOCK_GROUPS_READ,
                ],
                'is_superadmin' => false,
            ],
            [
                'name' => 'Billing',
                'description' => 'Manages accounts and reads what they own.',
                'permissions' => [
                    AdminPermission::OVERVIEW_READ,
                    AdminPermission::AUDIT_LOGS_READ,
                    AdminPermission::USERS_READ,
                    AdminPermission::USERS_MANAGE,
                    AdminPermission::SERVERS_READ,
                ],
                'is_superadmin' => false,
            ],
            [
                'name' => 'Network',
                'description' => 'Owns nodes, locations and address space.',
                'permissions' => [
                    AdminPermission::OVERVIEW_READ,
                    AdminPermission::LOCATIONS_READ,
                    AdminPermission::LOCATIONS_MANAGE,
                    AdminPermission::NODES_READ,
                    AdminPermission::NODES_MANAGE,
                    AdminPermission::ADDRESS_BLOCK_GROUPS_READ,
                    AdminPermission::ADDRESS_BLOCK_GROUPS_MANAGE,
                    AdminPermission::ANCHORS_READ,
                ],
                'is_superadmin' => false,
            ],
        ];

        foreach ($roles as $role) {
            DB::table('admin_roles')->insert([
                'uuid' => Str::uuid()->toString(),
                'name' => $role['name'],
                'description' => $role['description'],
                'permissions' => json_encode(
                    array_map(fn (AdminPermission $p) => $p->value, $role['permissions']),
                ),
                'is_system' => true,
                'is_superadmin' => $role['is_superadmin'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
