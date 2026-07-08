<?php

namespace App\Support\Api;

/**
 * The scoped-ability vocabulary for the admin/application API (`/api/application`). Resources are
 * the admin surface's top-level segments; see {@see ScopedTokenAbilities} for the semantics.
 */
final class TokenAbilities extends ScopedTokenAbilities
{
    /** Top-level resources of the application API (the first path segment). */
    public const RESOURCES = [
        'overview',
        'locations',
        'nodes',
        'servers',
        'address-block-groups',
        'template-groups',
        'users',
        'coterms',
    ];

    protected const PATH_PREFIX = 'api/application/';
}
