<?php

use App\Enums\Admin\AdminPermission;
use App\Enums\Api\ApiKeyType;
use App\Models\AdminRole;
use App\Models\User;

/**
 * Fine-grained admin permissions, in place of the single `root_admin` bit.
 */
beforeEach(function () {
    fakeProxmox();
});

it('migrates an existing root admin onto the unrestricted role', function () {
    // `root_admin` is no longer a column; the accessor over `admin_role_id` is what keeps the
    // provisioning paths that say it -- p:make-user, the seeders, the Application API -- meaning
    // what they always meant.
    $admin = User::factory()->create(['root_admin' => true]);

    expect($admin->admin_role_id)->toBe(AdminRole::superadmin()->id)
        ->and($admin->root_admin)->toBeTrue()
        ->and($admin->isAdmin())->toBeTrue();

    foreach (AdminPermission::cases() as $permission) {
        expect($admin->hasAdminPermission($permission))->toBeTrue();
    }
});

it('keeps an existing root admin able to reach everything', function () {
    $admin = admin();

    foreach ([
        '/api/admin/overview',
        '/api/admin/audit-logs',
        '/api/admin/locations',
        '/api/admin/nodes',
        '/api/admin/servers',
        '/api/admin/users',
        '/api/admin/admin-roles',
        '/api/admin/isos',
        '/api/admin/image-groups',
        '/api/admin/address-block-groups',
        '/api/admin/tokens',
        '/api/admin/settings/account',
        '/api/admin/settings/permissions',
    ] as $endpoint) {
        expect($this->actingAs($admin)->getJson($endpoint)->status())
            ->not->toBe(403, "root admin was refused {$endpoint}");
    }
});

it('shuts the admin area to an account with no role', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/admin/overview')
        ->assertForbidden();
});

it('admits a role only to what it holds', function () {
    $support = operator([AdminPermission::SERVERS_READ]);

    $this->actingAs($support)->getJson('/api/admin/servers')->assertOk();
    $this->actingAs($support)->getJson('/api/admin/nodes')->assertForbidden();
    $this->actingAs($support)->getJson('/api/admin/users')->assertForbidden();
});

it('treats manage as implying read on the same resource', function () {
    $network = operator([AdminPermission::NODES_MANAGE]);

    $this->actingAs($network)->getJson('/api/admin/nodes')->assertOk();
    $this->actingAs($network)->postJson('/api/admin/nodes/test-connection', [])
        ->assertStatus(422);
});

it('does not let read imply write', function () {
    $this->actingAs(operator([AdminPermission::LOCATIONS_READ]))
        ->postJson('/api/admin/locations', [])
        ->assertForbidden();
});

it('folds a resource\'s siblings in with it', function () {
    // Storages and clusters are part of administering nodes; presets and a stray backup part of
    // administering servers. Neither is its own permission an operator has to think about.
    $network = operator([AdminPermission::NODES_READ]);
    $this->actingAs($network)->getJson('/api/admin/storages')->assertOk();

    $fleet = operator([AdminPermission::SERVERS_READ]);
    $this->actingAs($fleet)->getJson('/api/admin/server-presets')->assertOk();
});

describe('the support carve-out', function () {
    it('lets a power-only role restart a server it cannot otherwise change', function () {
        [, , , $server] = createServerModel();
        $support = operator([AdminPermission::SERVERS_READ, AdminPermission::SERVERS_POWER]);

        expect($this->actingAs($support)
            ->postJson("/api/admin/servers/{$server->id}/power", ['command' => 'restart'])
            ->status())->not->toBe(403);

        $this->actingAs($support)
            ->deleteJson("/api/admin/servers/{$server->id}")
            ->assertForbidden();
    });

    it('does not hand power out with plain read access', function () {
        [, , , $server] = createServerModel();

        $this->actingAs(operator([AdminPermission::SERVERS_READ]))
            ->postJson("/api/admin/servers/{$server->id}/power", ['command' => 'restart'])
            ->assertForbidden();
    });
});

describe('impersonation', function () {
    it('is not part of managing users', function () {
        $target = User::factory()->create();

        $this->actingAs(operator([AdminPermission::USERS_MANAGE]))
            ->postJson("/api/admin/users/{$target->id}/generate-sso-token")
            ->assertForbidden();
    });

    it('is its own permission', function () {
        $target = User::factory()->create();

        $this->actingAs(operator([AdminPermission::USERS_IMPERSONATE]))
            ->postJson("/api/admin/users/{$target->id}/generate-sso-token")
            ->assertSuccessful();
    });
});

it('ships four roles an operator can assign without building one', function () {
    $names = AdminRole::query()->where('is_system', true)->orderBy('id')->pluck('name')->all();

    expect($names)->toBe(['Superadmin', 'Support', 'Billing', 'Network']);
});

it('refuses to delete a built-in role', function () {
    $role = AdminRole::query()->where('name', 'Support')->firstOrFail();

    $this->actingAs(admin())
        ->deleteJson("/api/admin/admin-roles/{$role->uuid}")
        ->assertBadRequest();
});

it('refuses to delete a role somebody still holds', function () {
    $holder = operator([AdminPermission::USERS_READ]);

    $this->actingAs(admin())
        ->deleteJson("/api/admin/admin-roles/{$holder->adminRole->uuid}")
        ->assertBadRequest();
});

it('refuses to rewrite a built-in role\'s permissions', function () {
    $role = AdminRole::query()->where('name', 'Support')->firstOrFail();

    $this->actingAs(admin())
        ->putJson("/api/admin/admin-roles/{$role->uuid}", [
            'name' => 'Support',
            'description' => $role->description,
            'permissions' => [AdminPermission::TOKENS_MANAGE->value],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('permissions');
});

it('lets an operator clone a built-in role and edit the copy', function () {
    $support = AdminRole::query()->where('name', 'Support')->firstOrFail();

    $this->actingAs(admin())
        ->postJson('/api/admin/admin-roles', [
            'name' => 'Support and Network',
            'description' => null,
            'permissions' => [
                ...$support->permissions,
                AdminPermission::NODES_MANAGE->value,
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.isSystem', false);

    $clone = AdminRole::query()->where('name', 'Support and Network')->firstOrFail();

    expect($clone->grants(AdminPermission::SERVERS_POWER))->toBeTrue()
        ->and($clone->grants(AdminPermission::NODES_MANAGE))->toBeTrue();
});

it('keeps authoring roles to a full administrator', function () {
    // Three of the permissions are each a path back to full control, so an operator who could
    // mint a role holding them could promote themselves in one step.
    $this->actingAs(operator([AdminPermission::USERS_MANAGE]))
        ->postJson('/api/admin/admin-roles', [
            'name' => 'Backdoor',
            'description' => null,
            'permissions' => [AdminPermission::TOKENS_MANAGE->value],
        ])
        ->assertForbidden();

    // Reading them is part of managing accounts, because assigning one needs the list.
    $this->actingAs(operator([AdminPermission::USERS_READ]))
        ->getJson('/api/admin/admin-roles')
        ->assertOk();
});

it('rejects a permission that is not in the catalog', function () {
    $this->actingAs(admin())
        ->postJson('/api/admin/admin-roles', [
            'name' => 'Nonsense',
            'description' => null,
            'permissions' => ['everything.always'],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('permissions.0');
});

describe('assigning a role', function () {
    it('revokes the API tokens an account held under its old role', function () {
        $target = operator([AdminPermission::NODES_MANAGE]);
        $target->createToken('old', ApiKeyType::ACCOUNT);

        $this->actingAs(admin())
            ->patchJson("/api/admin/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
                'admin_role_id' => null,
            ])
            ->assertSuccessful();

        expect($target->fresh()->isAdmin())->toBeFalse()
            ->and($target->tokens()->count())->toBe(0);
    });

    it('refuses to change the role on your own account', function () {
        $self = admin();

        $this->actingAs($self)
            ->patchJson("/api/admin/users/{$self->id}", [
                'name' => $self->name,
                'email' => $self->email,
                'admin_role_id' => null,
            ])
            ->assertBadRequest();

        expect($self->fresh()->isAdmin())->toBeTrue();
    });

    it('still accepts the root_admin field the Application API has always taken', function () {
        $target = User::factory()->create();

        $this->actingAs(admin())
            ->patchJson("/api/admin/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
                'root_admin' => true,
            ])
            ->assertSuccessful();

        expect($target->fresh()->admin_role_id)->toBe(AdminRole::superadmin()->id);
    });

    it('leaves the role alone when the payload mentions neither field', function () {
        $target = operator([AdminPermission::USERS_READ]);
        $roleId = $target->admin_role_id;

        $this->actingAs(admin())
            ->patchJson("/api/admin/users/{$target->id}", [
                'name' => 'Renamed',
                'email' => $target->email,
            ])
            ->assertSuccessful();

        expect($target->fresh()->admin_role_id)->toBe($roleId);
    });
});

it('intersects a token\'s abilities with its owner\'s role', function () {
    // Two gates, both enforced: the token scopes the credential, the role scopes the account.
    $operator = operator([AdminPermission::LOCATIONS_READ]);
    $token = $operator->createToken('scoped', ApiKeyType::ACCOUNT, ['*']);

    $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
        ->getJson('/api/application/locations')
        ->assertOk();

    // The wildcard ability does not widen what the account may do.
    $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
        ->getJson('/api/application/nodes')
        ->assertForbidden();
});
