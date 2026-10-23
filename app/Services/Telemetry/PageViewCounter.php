<?php

namespace Convoy\Services\Telemetry;

use Illuminate\Support\Facades\Cache;

/**
 * How often each page was opened since the last telemetry report.
 *
 * Pages are client-side routes, so the server never sees a navigation: the
 * router posts the pattern of the page it lands on ("/servers/:id/backups",
 * never the address itself) to POST /api/client/telemetry/views. Only the
 * patterns below are counted, so whatever else a browser sends is dropped and
 * never reaches the report.
 */
class PageViewCounter
{
    /**
     * Route patterns as resources/scripts/routers builds them, and the name each
     * page is reported under. Layouts and redirects are left out, and the page
     * they lead to is counted instead: /admin/ipam/:poolId goes on to its
     * addresses, and a settings index such as /servers/:id/settings goes on to
     * its general tab on a wide screen (on a narrow one it's only a menu).
     */
    public const PAGES = [
        '/' => 'client.servers.index',
        '/servers/:id' => 'client.servers.show',
        '/servers/:id/backups' => 'client.servers.backups',
        '/servers/:id/settings/general' => 'client.servers.settings.general',
        '/servers/:id/settings/hardware' => 'client.servers.settings.hardware',
        '/servers/:id/settings/network' => 'client.servers.settings.network',
        '/servers/:id/settings/security' => 'client.servers.settings.security',
        '/servers/:id/terminal' => 'client.servers.terminal',
        '/admin' => 'admin.overview',
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
        '/admin/ipam' => 'admin.address-pools.index',
        '/admin/ipam/:poolId/addresses' => 'admin.address-pools.addresses',
        '/admin/users' => 'admin.users.index',
        '/admin/users/:id/settings/general' => 'admin.users.settings.general',
        '/admin/users/:id/servers' => 'admin.users.servers',
        '/admin/coterms' => 'admin.coterms.index',
        '/admin/tokens' => 'admin.tokens.index',
    ];

    public function record(string $pattern): void
    {
        if (isset(self::PAGES[$pattern])) {
            Cache::increment($this->key(self::PAGES[$pattern]));
        }
    }

    /**
     * @return array<string, int> Pages opened at least once, by name
     */
    public function counts(): array
    {
        $names = array_values(self::PAGES);
        $values = Cache::many(array_map($this->key(...), $names));
        $counts = [];

        foreach ($names as $name) {
            if (($count = (int) ($values[$this->key($name)] ?? 0)) > 0) {
                $counts[$name] = $count;
            }
        }

        return $counts;
    }

    /**
     * Reads the counts and takes them off. Each is lowered by what was read
     * rather than cleared, so a view recorded in between is left for the next
     * report: it is neither lost nor counted twice.
     *
     * @return array<string, int>
     */
    public function flush(): array
    {
        $counts = $this->counts();

        foreach ($counts as $name => $count) {
            Cache::decrement($this->key($name), $count);
        }

        return $counts;
    }

    private function key(string $name): string
    {
        return "telemetry:views:{$name}";
    }
}
