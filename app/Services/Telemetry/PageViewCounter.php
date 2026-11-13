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
 *
 * Nothing is reset by reading. The report sends what {@see counts()} returned,
 * and only once the console has accepted it does {@see subtract()} take those
 * exact amounts back off.
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
        '/admin/settings/updates' => 'admin.settings.updates',
        '/admin/settings/telemetry' => 'admin.settings.telemetry',
        '/admin/settings/upgrade' => 'admin.settings.upgrade',
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
     * Takes what a report the console accepted carried back off the counters.
     * Each is lowered by what was sent rather than cleared, so a view recorded
     * while the report was in flight is left for the next one: it is neither
     * lost nor counted twice.
     *
     * @param  array<string, int>  $counts
     */
    public function subtract(array $counts): void
    {
        $names = array_flip(self::PAGES);

        foreach ($counts as $name => $count) {
            if (isset($names[$name]) && $count > 0) {
                Cache::decrement($this->key($name), $count);
            }
        }
    }

    /** Drops every count, when telemetry is turned off. */
    public function clear(): void
    {
        foreach (array_values(self::PAGES) as $name) {
            Cache::forget($this->key($name));
        }
    }

    private function key(string $name): string
    {
        return "telemetry:views:{$name}";
    }
}
