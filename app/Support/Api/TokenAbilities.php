<?php

namespace App\Support\Api;

/**
 * The scoped-ability vocabulary for the admin/application API (`/api/application`). Resources are
 * the admin surface's top-level segments; see {@see ScopedTokenAbilities} for the semantics.
 */
final class TokenAbilities extends ScopedTokenAbilities
{
    /**
     * Top-level resources of the application API (the first path segment).
     *
     * Every first segment in `routes/api-admin.php` is listed, because
     * {@see ScopedTokenAbilities::requiredFor()} demands `*` for anything absent — so a segment
     * left out is not merely unscopable, it is unreachable by every token but a wildcard one.
     * `tokens` is the deliberate exception: that group carries `DenyApiTokenAccess`, so no token
     * reaches it at all and an ability for it would be a lie.
     */
    public const RESOURCES = [
        'overview',
        'version',
        'audit-logs',
        'locations',
        'nodes',
        'clusters',
        'storages',
        'servers',
        'server-presets',
        'adoptable-guests',
        'backups',
        'address-block-groups',
        'images',
        'image-groups',
        'isos',
        'users',
        'admin-roles',
        'anchors',
        'relays',
        'settings',
    ];

    protected const PATH_PREFIX = 'api/application/';
}
