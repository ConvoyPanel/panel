<?php

namespace Convoy\Services\Upgrade;

use Convoy\Enums\Server\Status;
use Convoy\Models\ActivityLog;
use Convoy\Models\AddressPool;
use Convoy\Models\Backup;
use Convoy\Models\Coterm;
use Convoy\Models\ISO;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * What Convoy 6's migrations will do with this install's data, item by item
 * (the list in the 6.0 upgrade runbook, "What the migrations throw away").
 * Read-only: it counts, and changes nothing.
 */
class UpgradeCheckService
{
    /** The data is not carried over. */
    public const DROPPED = 'dropped';

    /** The data is carried over, but changed. */
    public const CHANGED = 'changed';

    /**
     * @return list<array{key: string, title: string, effect: string, affected: bool, count: int, summary: string, action: ?string, details: list<string>}>
     */
    public function items(): array
    {
        return [
            $this->templates(),
            $this->isos(),
            $this->activity(),
            $this->snapshots(),
            $this->coterms(),
            $this->failedBackups(),
            $this->deletedBackups(),
            $this->nodes(),
            $this->addressPools(),
            $this->removedStatuses(),
        ];
    }

    /** How many items find something on this install. */
    public function affectedCount(): int
    {
        return count(array_filter($this->items(), fn (array $item) => $item['affected']));
    }

    private function templates(): array
    {
        $groups = TemplateGroup::query()->count();
        $templates = Template::query()->count();

        return $this->item(
            key: 'templates',
            title: 'Templates and template groups',
            effect: self::DROPPED,
            count: $groups + $templates,
            summary: $groups + $templates > 0
                ? sprintf(
                    'Convoy 6 replaces templates with an image library. The %s and %s here are not carried over. Servers keep their disks, but reinstalling one needs an image from the new library.',
                    self::plural($groups, 'template group'), self::plural($templates, 'template'),
                )
                : 'Convoy 6 replaces templates with an image library. This panel has no templates, so nothing is lost.',
            action: $templates > 0 ? 'Note which templates your users install from, so you can add them as images after upgrading.' : null,
        );
    }

    private function isos(): array
    {
        $isos = ISO::query()->count();

        return $this->item(
            key: 'isos',
            title: 'ISOs',
            effect: self::DROPPED,
            count: $isos,
            summary: $isos > 0
                ? sprintf('ISOs become one library for the whole panel in Convoy 6. The %s listed under nodes here %s not carried over.', self::plural($isos, 'ISO', 'ISOs'), $isos === 1 ? 'is' : 'are')
                : 'ISOs become one library for the whole panel in Convoy 6. This panel lists no ISOs.',
            action: $isos > 0 ? 'Add the ISOs your users need to the library after upgrading.' : null,
        );
    }

    private function activity(): array
    {
        $entries = ActivityLog::query()->count();

        return $this->item(
            key: 'activity',
            title: 'Activity logs',
            effect: self::DROPPED,
            count: $entries,
            summary: $entries > 0
                ? sprintf('Convoy 6 has a new audit log, which starts empty. The %s here are not carried over.', self::plural($entries, 'activity log entry', 'activity log entries'))
                : 'Convoy 6 has a new audit log, which starts empty. This panel has no activity log entries.',
            action: $entries > 0 ? 'Export them before upgrading, with `convoy export-activity` or `php artisan activity:export --output=activity.csv`.' : null,
        );
    }

    private function snapshots(): array
    {
        $limited = Server::query()->where('snapshot_limit', '>', 0)->count();

        return $this->item(
            key: 'snapshots',
            title: 'Snapshots',
            effect: self::DROPPED,
            count: $limited,
            summary: $limited > 0
                ? sprintf('Convoy 6 has no snapshots. Users can no longer take or roll back snapshots from the panel, and the snapshot limits on %s are dropped.', self::plural($limited, 'server'))
                : 'Convoy 6 has no snapshots. No server here has a snapshot limit, so nothing changes for users.',
        );
    }

    private function coterms(): array
    {
        $coterms = Coterm::query()->count();
        $nodes = Node::query()->whereNotNull('coterm_id')->count();

        return $this->item(
            key: 'coterms',
            title: 'Coterm',
            effect: self::DROPPED,
            count: $coterms,
            summary: $coterms > 0
                ? sprintf(
                    'Convoy 6 replaces Coterm with the Anchor agent. The %s and %s are dropped, and the browser console works again once Anchor runs on each node.',
                    self::plural($coterms, 'Coterm'), $nodes === 1 ? 'its link to 1 node' : "their links to {$nodes} nodes",
                )
                : 'Convoy 6 replaces Coterm with the Anchor agent, which each node needs for the browser console. This panel uses no Coterm.',
            action: $nodes > 0 ? 'Plan to install Anchor on each node that uses Coterm today.' : null,
        );
    }

    private function failedBackups(): array
    {
        $failed = Backup::withTrashed()
            ->whereNotNull('completed_at')
            ->where('is_successful', false)
            ->count();

        return $this->item(
            key: 'failed_backups',
            title: 'Failed backups',
            effect: self::DROPPED,
            count: $failed,
            summary: $failed > 0
                ? ($failed === 1
                    ? 'Convoy 6 deletes the records of backups that finished unsuccessfully. There is 1 here; it holds no data.'
                    : sprintf('Convoy 6 deletes the records of backups that finished unsuccessfully. There are %d here; they hold no data.', $failed))
                : 'Convoy 6 deletes the records of backups that finished unsuccessfully. There are none here.',
        );
    }

    private function deletedBackups(): array
    {
        $deleted = Backup::onlyTrashed()->count();

        return $this->item(
            key: 'deleted_backups',
            title: 'Deleted backups',
            effect: self::CHANGED,
            count: $deleted,
            summary: $deleted > 0
                ? sprintf(
                    'This panel keeps the records of deleted backups and hides them. Convoy 6 has no such hidden records, so %s.',
                    $deleted === 1 ? 'the 1 deleted backup comes back into its server\'s backup list' : "the {$deleted} deleted backups come back into their servers' backup lists",
                )
                : 'This panel keeps the records of deleted backups and hides them; Convoy 6 would show them again. There are none here.',
            action: $deleted > 0 ? 'After upgrading, check those backup lists and delete what users should no longer see.' : null,
        );
    }

    private function nodes(): array
    {
        $nodes = Node::query()->count();
        $clusters = Node::query()->distinct()->count('cluster');

        return $this->item(
            key: 'nodes',
            title: 'Node CPU topology and clusters',
            effect: self::CHANGED,
            count: $nodes,
            summary: $nodes > 0
                ? ($nodes === 1
                    ? 'The node starts on Convoy 6 with 1 socket, 1 core and 1 CPU until it reports its real CPU topology, and becomes its own cluster.'
                    : sprintf(
                        'Each of the %d nodes starts on Convoy 6 with 1 socket, 1 core and 1 CPU until it reports its real CPU topology, and each becomes its own cluster (they are in %s here).',
                        $nodes, self::plural($clusters, 'cluster'),
                    ))
                : 'Nodes start on Convoy 6 with a placeholder CPU topology until they report in. This panel has no nodes.',
        );
    }

    /**
     * Convoy 6 keeps the MAC address on the address block, so a pool whose
     * addresses carry their own MACs becomes one block per MAC, plus one for the
     * addresses without.
     */
    private function addressPools(): array
    {
        $pools = DB::table('ip_addresses')
            ->select('address_pool_id')
            ->selectRaw('COUNT(DISTINCT LOWER(mac_address)) AS macs')
            ->selectRaw('SUM(CASE WHEN mac_address IS NULL THEN 1 ELSE 0 END) AS without_mac')
            ->groupBy('address_pool_id')
            ->havingRaw('COUNT(mac_address) > 0')
            ->get();

        $names = AddressPool::query()->whereIn('id', $pools->pluck('address_pool_id'))->pluck('name', 'id');

        $details = $pools
            ->sortBy('address_pool_id')
            ->map(function (object $pool) use ($names) {
                $blocks = (int) $pool->macs + ((int) $pool->without_mac > 0 ? 1 : 0);

                return sprintf(
                    '%s: %s with their own MAC address, so it becomes %s.',
                    $names[$pool->address_pool_id] ?? "Pool #{$pool->address_pool_id}",
                    self::plural((int) $pool->macs, 'distinct MAC', 'distinct MACs'),
                    self::plural($blocks, 'address block'),
                );
            })
            ->values()
            ->all();

        return $this->item(
            key: 'address_pools',
            title: 'Address pools with per-address MACs',
            effect: self::CHANGED,
            count: count($details),
            summary: $details !== []
                ? sprintf(
                    'Convoy 6 keeps a MAC address on the address block, not on each address. %s here %s addresses with their own MAC, so %s split into one overlapping block per MAC, plus one for the addresses without. Every address and its MAC is kept.',
                    self::plural(count($details), 'pool'), count($details) === 1 ? 'has' : 'have', count($details) === 1 ? 'it is' : 'each is',
                )
                : 'Convoy 6 keeps a MAC address on the address block, not on each address. No address here has its own MAC, so every pool becomes one block.',
            details: $details,
        );
    }

    private function removedStatuses(): array
    {
        $servers = Server::query()->where('status', Status::RESTORING_SNAPSHOT->value)->count();

        return $this->item(
            key: 'removed_statuses',
            title: 'Servers restoring a snapshot',
            effect: self::CHANGED,
            count: $servers,
            summary: $servers > 0
                ? sprintf('Convoy 6 has no snapshot restores, so it marks the %s still shown as restoring a snapshot as ready.', self::plural($servers, 'server'))
                : 'Convoy 6 marks servers still shown as restoring a snapshot as ready. There are none here.',
            action: $servers > 0 ? 'Let those restores finish, or check those servers on Proxmox, before upgrading.' : null,
        );
    }

    /** @param  list<string>  $details */
    private function item(
        string $key,
        string $title,
        string $effect,
        int $count,
        string $summary,
        ?string $action = null,
        array $details = [],
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'effect' => $effect,
            'affected' => $count > 0,
            'count' => $count,
            'summary' => $summary,
            'action' => $count > 0 ? $action : null,
            'details' => $details,
        ];
    }

    private static function plural(int $count, string $singular, ?string $plural = null): string
    {
        return $count.' '.($count === 1 ? $singular : ($plural ?? Str::plural($singular)));
    }
}
