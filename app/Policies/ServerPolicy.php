<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ServerPermission;
use App\Models\Server;
use App\Models\User;

/**
 * Who may do what on one server, in the client area.
 *
 * Three kinds of caller reach this: the owner, an operator with `servers.manage`, and a sub-user.
 * The first two are answered by {@see before()}; everything else falls through to the sub-user's
 * stored grant.
 *
 * Ability names stay the camelCase ones the request classes already use, and
 * {@see ServerPermission::abilityMap()} is what ties them to the stored permission values. An
 * ability with no entry there is denied, so an endpoint added without classifying it is closed
 * rather than open -- `ServerPermissionCoverageTest` is what turns that into a failing build
 * instead of a silent 403.
 */
class ServerPolicy
{
    public function before(User $user, string $ability, Server $server): ?bool
    {
        if ($user->id === $server->user_id) {
            return true;
        }

        // Read access to the admin area is not permission to drive a customer's server from the
        // client API; managing servers is.
        if ($user->hasAdminPermission(AdminPermission::SERVERS_MANAGE)) {
            return true;
        }

        // The support carve-out. `servers.power` is deliberately narrower than `servers.manage`,
        // and the admin power endpoint shares this policy with the client one, so the exception
        // has to be spelled out here or a Support role would be refused the one thing it is for.
        if ($ability === 'sendPowerCommand' && $user->hasAdminPermission(AdminPermission::SERVERS_POWER)) {
            return true;
        }

        return null;
    }

    /** Reaching the server at all. Every sub-user can, whatever else they hold. */
    public function view(User $user, Server $server): bool
    {
        return $server->subuserFor($user) !== null;
    }

    /**
     * Managing who else may reach the server.
     *
     * Reachable only through `before()` -- the owner, or an operator who manages servers. There
     * is deliberately no sub-user permission for it: one that could grant permissions could grant
     * itself every other one, so a share could silently become full control of the server.
     */
    public function manageSubusers(User $user, Server $server): bool
    {
        return false;
    }

    /**
     * The device list, which two screens open with.
     *
     * Reordering boot devices and picking a disk to rebuild onto both start from the same read,
     * so either permission opens it rather than inventing a third that nobody would think to
     * grant.
     */
    public function viewStorage(User $user, Server $server): bool
    {
        return $this->granted($user, $server, ServerPermission::SETTINGS_BOOT_ORDER)
            || $this->granted($user, $server, ServerPermission::SETTINGS_REINSTALL);
    }

    /**
     * One endpoint, four permissions.
     *
     * `POST /power` carries the command in its body, so the gate has to see it too. Called as
     * `can('sendPowerCommand', [$server, $command])`.
     */
    public function sendPowerCommand(User $user, Server $server, ?PowerCommand $command = null): bool
    {
        // No command means the caller could not parse one, which validation is about to reject
        // anyway. Denying here keeps a malformed body from reaching the hypervisor path.
        if ($command === null) {
            return false;
        }

        return $this->granted($user, $server, ServerPermission::forPowerCommand($command));
    }

    /**
     * Everything else.
     *
     * Kept as `__call` rather than twenty near-identical methods: the mapping is data, and a
     * method per permission would be a second place to keep it in step. Laravel resolves a policy
     * method with `is_callable()`, which `__call` satisfies.
     */
    public function __call(string $name, mixed $arguments): bool
    {
        $permission = ServerPermission::abilityMap()[$name] ?? null;

        if ($permission === null) {
            return false;
        }

        [$user, $server] = $arguments;

        return $this->granted($user, $server, $permission);
    }

    private function granted(User $user, Server $server, ServerPermission $permission): bool
    {
        return (bool) $server->subuserFor($user)?->grants($permission);
    }
}
