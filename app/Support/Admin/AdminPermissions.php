<?php

namespace App\Support\Admin;

use App\Enums\Admin\AdminPermission;
use App\Support\Api\ScopedTokenAbilities;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Derives the admin permission a request requires, the way
 * {@see ScopedTokenAbilities::requiredFor()} derives a token ability.
 *
 * The resource is the first path segment under the admin prefix, read through
 * {@see self::RESOURCES} so that sibling groups fold into the thing they belong to -- storages
 * and clusters are part of administering nodes, presets and stray backups part of administering
 * servers. The action is `read` for GET/HEAD and `manage` for everything else.
 *
 * A segment with no entry resolves to `null`, which only a superadmin satisfies. A route added
 * without a catalog entry is therefore closed rather than open, and
 * `RouteAdminPermissionCoverageTest` fails until it is classified.
 */
final class AdminPermissions
{
    /** Path segment => the permission resource that governs it. */
    private const RESOURCES = [
        'overview' => 'overview',
        // What the panel is running and whether a newer release exists. It sits on the dashboard,
        // and `POST /version/check` is a refresh rather than a change, so both fold into
        // `overview.read` -- see the fallback in self::requiredFor().
        'version' => 'overview',
        'audit-logs' => 'audit-logs',
        'locations' => 'locations',
        'nodes' => 'nodes',
        'storages' => 'nodes',
        'clusters' => 'nodes',
        'servers' => 'servers',
        'server-presets' => 'servers',
        // Adopting an unmanaged guest is how a server comes into existence without a
        // build, so it is administering servers rather than a surface of its own.
        'adoptable-guests' => 'servers',
        'backups' => 'servers',
        'address-block-groups' => 'address-block-groups',
        'images' => 'image-groups',
        'image-groups' => 'image-groups',
        'isos' => 'isos',
        'users' => 'users',
        'admin-roles' => 'users',
        'anchors' => 'anchors',
        'relays' => 'anchors',
        'settings' => 'settings',
        // No `tokens.read`: listing panel-wide API tokens is part of managing them, and the
        // fallback below picks `tokens.manage` for the GET as well.
        'tokens' => 'tokens',
    ];

    /**
     * Routes whose permission is not the one their resource and method imply.
     *
     * Keyed `METHOD path`, with `*` standing in for a bound parameter. Both entries exist because
     * the operator who needs them is not the operator who should hold `manage`.
     *
     * @var array<string, AdminPermission>
     */
    private const OVERRIDES = [
        'POST servers/*/power' => AdminPermission::SERVERS_POWER,
        'POST users/*/generate-sso-token' => AdminPermission::USERS_IMPERSONATE,
    ];

    /** The prefixes the admin route file is mounted under. */
    private const PREFIXES = ['api/admin/', 'api/application/'];

    /**
     * The permission this request requires, or null when nothing in the catalog covers it.
     */
    public static function requiredFor(Request $request): ?AdminPermission
    {
        $path = self::stripPrefix($request->path());

        if ($override = self::override($request->method(), $path)) {
            return $override;
        }

        $resource = self::RESOURCES[Str::before($path, '/')] ?? null;

        if ($resource === null) {
            return null;
        }

        $action = in_array($request->method(), ['GET', 'HEAD'], true) ? 'read' : 'manage';

        // A resource that has only one of the two actions answers for both: `tokens` has no read,
        // and `overview` no manage.
        return AdminPermission::tryFrom("{$resource}.{$action}")
            ?? AdminPermission::tryFrom("{$resource}.manage")
            ?? AdminPermission::tryFrom("{$resource}.read");
    }

    private static function override(string $method, string $path): ?AdminPermission
    {
        foreach (self::OVERRIDES as $pattern => $permission) {
            [$patternMethod, $patternPath] = explode(' ', $pattern, 2);

            if ($method === $patternMethod && Str::is($patternPath, $path)) {
                return $permission;
            }
        }

        return null;
    }

    private static function stripPrefix(string $path): string
    {
        foreach (self::PREFIXES as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                return trim(Str::after($path, $prefix), '/');
            }
        }

        return trim($path, '/');
    }
}
