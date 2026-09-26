<?php

namespace App\Enums\Admin;

use App\Support\Api\ScopedTokenAbilities;

/**
 * What an admin role may reach in the admin area.
 *
 * Values are `<resource>.<action>`, where the resource is the first path segment under
 * `api/admin/` -- the same key {@see ScopedTokenAbilities} scopes a token by, so
 * the two vocabularies name the same things. `manage` implies `read` on the same resource, the
 * way `{resource}:write` implies `{resource}:read`.
 *
 * Token abilities and these permissions are both enforced and intersect: an API token can never
 * widen what the account behind it may do, and a role can never widen what the token was scoped
 * to.
 *
 * Two carve-outs exist because the persona that needs them is not the persona that should hold
 * `manage` over the whole resource:
 *  - {@see self::SERVERS_POWER} -- support staff reboot a customer's server without being able to
 *    delete it or rewrite its limits;
 *  - {@see self::USERS_IMPERSONATE} -- minting an SSO link signs the operator in *as* the
 *    customer, which is impersonation rather than administration.
 */
enum AdminPermission: string
{
    case OVERVIEW_READ = 'overview.read';
    case AUDIT_LOGS_READ = 'audit-logs.read';

    case LOCATIONS_READ = 'locations.read';
    case LOCATIONS_MANAGE = 'locations.manage';

    case NODES_READ = 'nodes.read';
    case NODES_MANAGE = 'nodes.manage';

    case SERVERS_READ = 'servers.read';
    case SERVERS_MANAGE = 'servers.manage';
    case SERVERS_POWER = 'servers.power';

    case ADDRESS_BLOCK_GROUPS_READ = 'address-block-groups.read';
    case ADDRESS_BLOCK_GROUPS_MANAGE = 'address-block-groups.manage';

    case IMAGE_GROUPS_READ = 'image-groups.read';
    case IMAGE_GROUPS_MANAGE = 'image-groups.manage';

    case ISOS_READ = 'isos.read';
    case ISOS_MANAGE = 'isos.manage';

    case USERS_READ = 'users.read';
    case USERS_MANAGE = 'users.manage';
    case USERS_IMPERSONATE = 'users.impersonate';

    case ANCHORS_READ = 'anchors.read';
    case ANCHORS_MANAGE = 'anchors.manage';

    case TOKENS_MANAGE = 'tokens.manage';

    case SETTINGS_READ = 'settings.read';
    case SETTINGS_MANAGE = 'settings.manage';

    /**
     * Whether holding this permission satisfies a requirement for another.
     *
     * Only the `manage`/`read` implication exists. `servers.power` does not imply `servers.read`,
     * and is never granted on its own in a default role for that reason.
     */
    public function satisfies(self $required): bool
    {
        if ($this === $required) {
            return true;
        }

        [$resource, $action] = explode('.', $this->value, 2);

        return $action === 'manage' && $required->value === "{$resource}.read";
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
