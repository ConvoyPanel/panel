import type { PaginatedResponse } from '@/lib/api'

/** A minted application (panel-wide) API token, as returned by the admin token API. */
export type ApiKey = App.Data.User.ApiKeyData

export type PaginatedApiKeys = PaginatedResponse<ApiKey>

/**
 * The resource-scoped ability vocabulary for the application API. Mirrors
 * `App\Support\Api\TokenAbilities::RESOURCES` on the backend — keep in sync.
 */
export const TOKEN_RESOURCES = [
    'overview',
    'locations',
    'nodes',
    'servers',
    'address-block-groups',
    'template-groups',
    'users',
    'coterms',
] as const

export type TokenResource = (typeof TOKEN_RESOURCES)[number]

/** Per-resource access level chosen in the ability picker. */
export type ResourceAccess = 'none' | 'read' | 'write'

/** Human labels for each resource, for the ability picker. */
export const resourceLabels: Record<TokenResource, string> = {
    overview: 'Overview',
    locations: 'Locations',
    nodes: 'Nodes',
    servers: 'Servers',
    'address-block-groups': 'IP address blocks',
    'template-groups': 'Templates',
    users: 'Users',
    coterms: 'Coterms',
}

/**
 * Turn a per-resource access map (plus the full-access toggle) into the backend
 * ability list: `*` for full access, else `{resource}:read` / `{resource}:write`
 * (write implies read) for each non-`none` resource.
 */
export const buildAbilities = (
    fullAccess: boolean,
    scopes: Record<TokenResource, ResourceAccess>
): string[] => {
    if (fullAccess) {
        return ['*']
    }

    return TOKEN_RESOURCES.flatMap(resource =>
        scopes[resource] === 'none' ? [] : [`${resource}:${scopes[resource]}`]
    )
}

/** A compact human summary of a token's granted abilities, for the list. */
export const summarizeAbilities = (abilities: string[]): string => {
    if (abilities.includes('*')) {
        return 'Full access'
    }

    if (abilities.length === 0) {
        return 'No access'
    }

    if (abilities.length <= 2) {
        return abilities.join(', ')
    }

    return `${abilities.length} abilities`
}
