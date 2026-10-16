<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\AdminRoleData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\AdminRoleFormRequest;
use App\Models\AdminRole;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * The roles an operator account can be given.
 *
 * Not paginated: an install has a handful of these, and the user form needs all of them in a
 * select. Reading them is part of `users.read`; authoring one is a superadmin act (see
 * {@see AdminRoleFormRequest::authorize()}).
 */
class AdminRoleController
{
    public function index()
    {
        $roles = AdminRole::query()
            ->withCount('users')
            ->orderByDesc('is_superadmin')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return AdminRoleData::collect($roles, DataCollection::class);
    }

    public function show(AdminRole $adminRole)
    {
        $adminRole->loadCount('users');

        return AdminRoleData::from($adminRole);
    }

    public function store(AdminRoleFormRequest $request)
    {
        $role = AdminRole::create([
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
            'permissions' => array_values(array_unique($request->array('permissions'))),
        ]);

        Audit::record(
            AuditEvent::ADMIN_ROLE_CREATED,
            subject: $role,
            properties: ['name' => $role->name, 'permissions' => $role->permissions],
        );

        $role->loadCount('users');

        return AdminRoleData::from($role);
    }

    public function update(AdminRoleFormRequest $request, AdminRole $adminRole)
    {
        $adminRole->update([
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
            // A system role reaches here only with its own permissions, which the request's
            // `after()` rule enforces; assigning them back is a no-op rather than a special case.
            'permissions' => $adminRole->is_system
                ? $adminRole->permissions
                : array_values(array_unique($request->array('permissions'))),
        ]);

        Audit::record(
            AuditEvent::ADMIN_ROLE_UPDATED,
            subject: $adminRole,
            properties: ['name' => $adminRole->name, 'permissions' => $adminRole->permissions],
        );

        $adminRole->loadCount('users');

        return AdminRoleData::from($adminRole);
    }

    public function destroy(AdminRole $adminRole)
    {
        if ($adminRole->is_system) {
            throw new BadRequestHttpException('A built-in role cannot be deleted.');
        }

        $adminRole->loadCount('users');

        // Deleting a role in use would silently strip admin access from everyone holding it. The
        // operator reassigns them first, deliberately.
        if ($adminRole->users_count > 0) {
            throw new BadRequestHttpException(
                'This role cannot be deleted while accounts are assigned to it.',
            );
        }

        $properties = ['name' => $adminRole->name, 'permissions' => $adminRole->permissions];

        $adminRole->delete();

        Audit::record(AuditEvent::ADMIN_ROLE_DELETED, subject: $adminRole, properties: $properties);

        return response()->noContent();
    }
}
