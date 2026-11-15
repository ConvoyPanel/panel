<?php

namespace Convoy\Services\Upgrade;

use Convoy\Models\AddressPool;
use Convoy\Models\Backup;
use Convoy\Models\Coterm;
use Convoy\Models\ISO;
use Convoy\Models\Node;
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
     * @return list<array{key: string, title: string, effect: string, affected: bool, count: int, summary: string, action: ?string, splits: list<array{pool: string, blocks: list<array{mac: ?string, addresses: int}>}>}>
     */
    public function items(): array
    {
        return [
            $this->templates(),
            $this->isos(),
            $this->coterms(),
            $this->failedBackups(),
            $this->deletedBackups(),
            $this->nodes(),
            $this->addressPools(),
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
        $about = "Convoy 6 replaces templates with images. Add an image once and Convoy downloads it to each node, so there's no template to set up in Proxmox.";

        return $this->item(
            key: 'templates',
            title: 'Templates',
            effect: self::DROPPED,
            count: $groups + $templates,
            summary: $groups + $templates > 0
                ? sprintf("{$about} The %s and %s here won't carry over.", self::plural($groups, 'template group'), self::plural($templates, 'template'))
                : "{$about} This panel has no templates.",
            action: $templates > 0 ? 'Before upgrading, note which templates you offer. After upgrading, add an image for each one, or pick ready-made ones from Images › Browse catalogue.' : null,
        );
    }

    private function isos(): array
    {
        $isos = ISO::query()->count();
        $about = 'Convoy 6 shares ISOs across nodes. Add an ISO once and Convoy downloads it to a node when a server there needs it.';

        return $this->item(
            key: 'isos',
            title: 'ISOs',
            effect: self::DROPPED,
            count: $isos,
            summary: $isos > 0
                ? sprintf("{$about} The %s here won't carry over.", self::plural($isos, 'ISO', 'ISOs'))
                : "{$about} This panel has no ISOs.",
            action: $isos > 0 ? 'After upgrading, add the ISOs you offer again.' : null,
        );
    }

    private function coterms(): array
    {
        $coterms = Coterm::query()->count();
        $nodes = Node::query()->whereNotNull('coterm_id')->count();
        $about = 'Convoy 6 replaces Coterm with Anchor, a service you install on each Proxmox node. It lets Convoy do more than the Proxmox API allows, including the browser console.';

        return $this->item(
            key: 'coterms',
            title: 'Coterm',
            effect: self::DROPPED,
            count: $coterms,
            summary: $coterms > 0
                ? sprintf("{$about} The %s here won't carry over.", self::plural($coterms, 'Coterm'))
                : "{$about} This panel doesn't use Coterm.",
            action: $nodes > 0
                ? sprintf('After upgrading, install Anchor on the %s that %s Coterm, so the browser console keeps working.', self::plural($nodes, 'node'), $nodes === 1 ? 'uses' : 'use')
                : null,
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
            title: 'Failed Backups',
            effect: self::DROPPED,
            count: $failed,
            summary: match (true) {
                $failed === 0 => 'Convoy 6 removes failed backups. There are none here.',
                $failed === 1 => 'Convoy 6 removes failed backups. The 1 here holds no data, so nothing is lost.',
                default => "Convoy 6 removes failed backups. The {$failed} here hold no data, so nothing is lost.",
            },
        );
    }

    private function deletedBackups(): array
    {
        $deleted = Backup::onlyTrashed()->count();
        $about = 'This panel hides deleted backups instead of removing them, and Convoy 6 shows them again.';

        return $this->item(
            key: 'deleted_backups',
            title: 'Deleted Backups',
            effect: self::CHANGED,
            count: $deleted,
            summary: match (true) {
                $deleted === 0 => "{$about} There are none here.",
                $deleted === 1 => "{$about} The 1 here will reappear in its server's backup list.",
                default => "{$about} The {$deleted} here will reappear in their servers' backup lists.",
            },
            action: $deleted > 0 ? 'After upgrading, delete them again.' : null,
        );
    }

    private function nodes(): array
    {
        $nodes = Node::query()->count();
        $about = "After the upgrade, Convoy 6 reads each node's CPUs and Proxmox cluster as soon as it can reach the node.";

        return $this->item(
            key: 'nodes',
            title: 'Nodes',
            effect: self::CHANGED,
            count: $nodes,
            summary: match (true) {
                $nodes === 0 => "{$about} This panel has no nodes.",
                $nodes === 1 => "{$about} Until then, it shows 1 CPU and its own cluster.",
                default => "{$about} Until then, each of the {$nodes} nodes here shows 1 CPU and its own cluster.",
            },
        );
    }

    /**
     * Convoy 6 keeps the MAC address on the address block, so a pool whose
     * addresses carry their own MACs becomes one block per MAC, plus one for the
     * addresses without.
     */
    private function addressPools(): array
    {
        $rows = DB::table('ip_addresses')
            ->select('address_pool_id')
            ->selectRaw('LOWER(mac_address) AS mac')
            ->selectRaw('COUNT(*) AS addresses')
            ->whereIn('address_pool_id', DB::table('ip_addresses')->select('address_pool_id')->whereNotNull('mac_address'))
            ->groupBy('address_pool_id', DB::raw('LOWER(mac_address)'))
            ->get();

        $names = AddressPool::query()->whereIn('id', $rows->pluck('address_pool_id'))->pluck('name', 'id');

        $splits = $rows
            ->groupBy('address_pool_id')
            ->sortKeys()
            ->map(fn ($blocks, $id) => [
                'pool' => $names[$id] ?? "Pool #{$id}",
                'blocks' => $blocks
                    ->sortBy(fn (object $block) => [$block->mac === null, $block->mac])
                    ->map(fn (object $block) => ['mac' => $block->mac, 'addresses' => (int) $block->addresses])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        $about = 'Convoy 6 sets a MAC address per address block instead of per address.';

        return $this->item(
            key: 'address_pools',
            title: 'Per-Address MACs',
            effect: self::CHANGED,
            count: count($splits),
            summary: $splits !== []
                ? "{$about} Each pool below is split into one block per MAC, with the same range. Every address keeps its MAC."
                : "{$about} No address here has its own MAC, so nothing changes.",
            splits: $splits,
        );
    }

    /** @param  list<array{pool: string, blocks: list<array{mac: ?string, addresses: int}>}>  $splits */
    private function item(
        string $key,
        string $title,
        string $effect,
        int $count,
        string $summary,
        ?string $action = null,
        array $splits = [],
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'effect' => $effect,
            'affected' => $count > 0,
            'count' => $count,
            'summary' => $summary,
            'action' => $count > 0 ? $action : null,
            'splits' => $splits,
        ];
    }

    private static function plural(int $count, string $singular, ?string $plural = null): string
    {
        return $count.' '.($count === 1 ? $singular : ($plural ?? Str::plural($singular)));
    }
}
