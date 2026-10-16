<?php

namespace App\Http\Requests\Admin\Users;

use App\Models\AdminRole;
use App\Models\User;

/**
 * Reads the admin role out of a user payload, in either spelling.
 *
 * `admin_role_id` is what the panel sends. `root_admin` is what the Application API has always
 * taken, and external callers (billing extensions, provisioning scripts) still send it -- it now
 * means the Superadmin role, which is exactly what it meant before. Accepting both is what keeps
 * an integration working across the upgrade.
 *
 * `admin_role_id` wins when both are present, so a caller that has moved on is never second-guessed.
 */
trait ResolvesAdminRole
{
    public function resolvedAdminRoleId(?User $existing = null): ?int
    {
        if ($this->has('admin_role_id')) {
            $id = $this->input('admin_role_id');

            return $id === null || $id === '' ? null : (int) $id;
        }

        if ($this->has('root_admin')) {
            return $this->boolean('root_admin') ? AdminRole::superadmin()->id : null;
        }

        return $existing?->admin_role_id;
    }
}
