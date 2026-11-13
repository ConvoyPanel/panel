<?php

use Convoy\Enums\Server\Status;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\Backup;
use Convoy\Models\ISO;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Services\Upgrade\UpgradeCheckService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** @return array<string, array<string, mixed>> */
function upgradeItems(): array
{
    return collect(app(UpgradeCheckService::class)->items())->keyBy('key')->all();
}

/** A v4 panel with something in every item the Convoy 6 upgrade drops or changes. */
function seedEverythingTheUpgradeTouches(): void
{
    [$user, $location, $node, $server] = createServerModel();
    $second = Node::factory()->for($location)->create(['cluster' => 'other-cluster']);

    $group = DB::table('template_groups')->insertGetId([
        'uuid' => Str::uuid(), 'name' => 'Linux', 'node_id' => $node->id, 'order_column' => 1,
    ]);
    DB::table('templates')->insert([
        ['uuid' => Str::uuid(), 'name' => 'Debian 13', 'template_group_id' => $group, 'vmid' => 9000, 'order_column' => 1],
        ['uuid' => Str::uuid(), 'name' => 'Ubuntu 24.04', 'template_group_id' => $group, 'vmid' => 9001, 'order_column' => 2],
    ]);

    ISO::factory()->for($node)->count(3)->create();

    $coterm = DB::table('coterms')->insertGetId([
        'name' => 'Console', 'is_tls_enabled' => true, 'fqdn' => 'coterm.test', 'port' => 443,
        'token_id' => Str::random(16), 'token' => Str::random(32),
    ]);
    Node::query()->whereKey([$node->id, $second->id])->update(['coterm_id' => $coterm]);

    $logs = collect(range(1, 4))->map(fn () => DB::table('activity_logs')->insertGetId([
        'event' => 'server:power.start', 'ip' => '127.0.0.1', 'properties' => '{}',
    ]));

    Backup::factory()->for($server)->create(['is_successful' => true]);
    Backup::factory()->for($server)->create(['is_successful' => false, 'completed_at' => now()]);
    Backup::factory()->for($server)->create(['is_successful' => false, 'completed_at' => null]);
    Backup::factory()->for($server)->create(['is_successful' => true])->delete();

    $server->forceFill(['snapshot_limit' => 3])->saveQuietly();
    Server::factory()->for($node)->for($user)->create(['status' => Status::RESTORING_SNAPSHOT->value, 'snapshot_limit' => 0]);

    $pinned = AddressPool::factory()->create(['name' => 'Pinned MACs']);
    foreach (['aa:bb:cc:00:00:01', 'AA:BB:CC:00:00:01', 'aa:bb:cc:00:00:02', null] as $i => $mac) {
        Address::factory()->create(['address_pool_id' => $pinned->id, 'type' => 'ipv4', 'address' => '192.0.2.'.($i + 10), 'cidr' => 24, 'gateway' => '192.0.2.1', 'mac_address' => $mac]);
    }
    $plain = AddressPool::factory()->create(['name' => 'Plain']);
    Address::factory()->create(['address_pool_id' => $plain->id, 'type' => 'ipv4', 'address' => '198.51.100.10', 'cidr' => 24, 'gateway' => '198.51.100.1', 'mac_address' => null]);
}

it('finds nothing to drop or change on an empty panel', function () {
    $items = upgradeItems();

    expect(array_keys($items))->toBe([
        'templates', 'isos', 'activity', 'snapshots', 'coterms', 'failed_backups',
        'deleted_backups', 'nodes', 'address_pools', 'removed_statuses',
    ]);

    foreach ($items as $item) {
        expect($item['affected'])->toBeFalse()
            ->and($item['action'])->toBeNull()
            ->and($item['summary'])->not->toBe('');
    }
});

it('says what Convoy 6 does with each kind of data this panel has', function () {
    seedEverythingTheUpgradeTouches();

    $items = upgradeItems();

    expect($items['templates'])->toMatchArray(['affected' => true, 'effect' => 'dropped', 'count' => 3])
        ->and($items['templates']['summary'])->toContain('1 template group and 2 templates')
        ->and($items['isos'])->toMatchArray(['count' => 3, 'effect' => 'dropped'])
        ->and($items['isos']['summary'])->toContain('The 3 ISOs listed under nodes here are not carried over.')
        ->and($items['failed_backups']['summary'])->toContain('There is 1 here; it holds no data.')
        ->and($items['deleted_backups']['summary'])->toContain('the 1 deleted backup comes back into its server\'s backup list')
        ->and($items['activity'])->toMatchArray(['count' => 4])
        ->and($items['activity']['action'])->toContain('convoy export-activity')
        ->and($items['snapshots'])->toMatchArray(['count' => 1])
        ->and($items['coterms'])->toMatchArray(['count' => 1])
        ->and($items['coterms']['summary'])->toContain('1 Coterm and their links to 2 nodes')
        ->and($items['coterms']['action'])->toContain('Anchor')
        ->and($items['failed_backups'])->toMatchArray(['count' => 1, 'effect' => 'dropped'])
        ->and($items['deleted_backups'])->toMatchArray(['count' => 1, 'effect' => 'changed'])
        ->and($items['nodes'])->toMatchArray(['count' => 2, 'effect' => 'changed'])
        ->and($items['nodes']['summary'])->toContain('2 nodes')->toContain('2 clusters')
        ->and($items['address_pools'])->toMatchArray(['count' => 1, 'effect' => 'changed'])
        ->and($items['address_pools']['details'])->toBe(['Pinned MACs: 2 distinct MACs with their own MAC address, so it becomes 3 address blocks.'])
        ->and($items['removed_statuses'])->toMatchArray(['count' => 1])
        ->and($items['removed_statuses']['summary'])->toContain('as ready');
});

it('changes nothing', function () {
    seedEverythingTheUpgradeTouches();

    $tables = ['templates', 'template_groups', 'iso_library', 'activity_logs', 'coterms', 'backups', 'servers', 'nodes', 'ip_addresses'];
    $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()]);

    app(UpgradeCheckService::class)->items();
    Artisan::call('convoy:upgrade-check');

    foreach ($tables as $table) {
        expect(DB::table($table)->orderBy('id')->get()->toJson())->toBe($before[$table]);
    }
});

it('prints the check on the command line', function () {
    seedEverythingTheUpgradeTouches();

    $this->artisan('convoy:upgrade-check')
        ->expectsOutputToContain('Templates and template groups [dropped]')
        ->expectsOutputToContain('What to do: Export them before upgrading, with `convoy export-activity`')
        ->expectsOutputToContain('Pinned MACs: 2 distinct MACs')
        ->expectsOutputToContain('10 of 10 items apply to this panel.')
        ->assertSuccessful();

    Artisan::call('convoy:upgrade-check', ['--json' => true]);

    expect(json_decode(Artisan::output(), true)['data'])->toHaveCount(10);
});

it('shows the check to admins only', function () {
    seedEverythingTheUpgradeTouches();

    $this->actingAs(User::factory()->create(['root_admin' => true]))
        ->getJson('/api/admin/settings/upgrade')
        ->assertOk()
        ->assertJsonCount(10, 'data.items')
        ->assertJsonPath('data.items.0.key', 'templates')
        ->assertJsonPath('data.items.0.affected', true);

    $this->actingAs(User::factory()->create())->getJson('/api/admin/settings/upgrade')->assertForbidden();
});
