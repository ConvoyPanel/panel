<?php

namespace Convoy\Services\Telemetry;

/**
 * Turns a frontend route pattern into the page key the `views` map is keyed
 * by, and refuses any pattern the frontend doesn't have.
 *
 * The panel is a client-side app, so the server never sees a page change. The
 * browser reports the route pattern it navigated to instead (react-router's
 * matched route paths joined, `/admin/nodes/:nodeId/templates`, never the
 * address with the node's id in it). That makes the pattern input from a user,
 * so it only counts if it is one of the pages below, and the key comes from
 * this list rather than from what the browser sent.
 *
 * The sign-in screen is not here: it is not usage. Nor are the routes that only
 * redirect, such as `/admin/servers/:id/settings`, which would otherwise count
 * the page they lead to twice.
 *
 * A new page in `resources/scripts/routers` belongs here too.
 * PageViewsTest fails for a route path it can't find in this list.
 */
class PageKeys
{
    /** @var array<string, string> route pattern => page key */
    public const PAGES = [
        '/' => 'index',

        '/servers/:id' => 'servers.show',
        '/servers/:id/backups' => 'servers.backups',
        '/servers/:id/settings/general' => 'servers.settings.general',
        '/servers/:id/settings/hardware' => 'servers.settings.hardware',
        '/servers/:id/settings/network' => 'servers.settings.network',
        '/servers/:id/settings/security' => 'servers.settings.security',
        '/servers/:id/terminal' => 'servers.terminal',

        '/admin' => 'admin.index',
        '/admin/locations' => 'admin.locations.index',

        '/admin/nodes' => 'admin.nodes.index',
        '/admin/nodes/:nodeId' => 'admin.nodes.show',
        '/admin/nodes/:nodeId/servers' => 'admin.nodes.servers',
        '/admin/nodes/:nodeId/isos' => 'admin.nodes.isos',
        '/admin/nodes/:nodeId/templates' => 'admin.nodes.templates',
        '/admin/nodes/:nodeId/addresses' => 'admin.nodes.addresses',
        '/admin/nodes/:nodeId/settings/general' => 'admin.nodes.settings.general',

        '/admin/servers' => 'admin.servers.index',
        '/admin/servers/:id' => 'admin.servers.show',
        '/admin/servers/:id/settings/general' => 'admin.servers.settings.general',
        '/admin/servers/:id/settings/hardware' => 'admin.servers.settings.hardware',

        '/admin/ipam' => 'admin.ipam.index',
        '/admin/ipam/:poolId/addresses' => 'admin.ipam.addresses',

        '/admin/users' => 'admin.users.index',
        '/admin/users/:id/settings/general' => 'admin.users.settings.general',
        '/admin/users/:id/servers' => 'admin.users.servers',

        '/admin/coterms' => 'admin.coterms.index',
        '/admin/tokens' => 'admin.tokens.index',
        '/admin/settings' => 'admin.settings.index',
    ];

    public function for(string $pattern): ?string
    {
        return self::PAGES[$pattern] ?? null;
    }
}
