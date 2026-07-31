<?php

use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\Vlan;
use Illuminate\Support\Facades\Schema;

/**
 * Re-runs the `vlans` migration over seeded data. Before that table existed a
 * VLAN was only implied by the tags in use, so the backfill is the one chance
 * to turn an existing fleet into declarations — a bridge that comes out empty
 * renders as a broken tree on an installation that was working fine.
 */
// Required once, and by a path relative to this file rather than
// `database_path()`: this runs while Pest is collecting tests, before the
// application is booted. Re-`require`ing it per test would also re-declare the
// migration's anonymous class in the same process, which segfaults PHP.
$migration = require __DIR__.'/../../../database/migrations/2026_07_25_000000_create_vlans_table.php';

$rerunMigration = function () use ($migration) {
    Schema::drop('vlans');

    $migration->up();
};

beforeEach(function () {
    $this->node = Node::factory()->create();
});

it('declares the bridge default and every tag in use', function () use ($rerunMigration) {
    $trunk = NetworkInterface::factory()->for($this->node)->trunk(100)->create();

    Server::factory()->for($this->node)->create([
        'network_interface_id' => $trunk->id,
        'vlan_tag' => 205,
    ]);
    // Inherits the default — already covered by the bridge's own tag, and must
    // not produce a duplicate row.
    Server::factory()->for($this->node)->create([
        'network_interface_id' => $trunk->id,
        'vlan_tag' => null,
    ]);
    // Two servers on one tag is still one VLAN.
    Server::factory()->count(2)->for($this->node)->create([
        'network_interface_id' => $trunk->id,
        'vlan_tag' => 310,
    ]);

    $rerunMigration();

    expect(Vlan::query()->orderBy('tag')->pluck('tag')->all())
        ->toBe([100, 205, 310])
        ->and(Vlan::query()->pluck('network_interface_id')->unique()->all())
        ->toBe([$trunk->id]);
});

it('does not promote a stray tag on a non-aware bridge', function () use ($rerunMigration) {
    $plain = NetworkInterface::factory()->for($this->node)->create();

    // Nothing at the schema level stops this, and the controller only clears
    // tags when awareness is toggled off — so a legacy row can carry one. It is
    // inert: ServerNetworkService forces null on a non-aware bridge.
    Server::factory()->for($this->node)->create([
        'network_interface_id' => $plain->id,
        'vlan_tag' => 999,
    ]);

    $rerunMigration();

    expect(Vlan::query()->count())->toBe(0);
});

it('leaves a pure trunk with only the tags its servers carry', function () use ($rerunMigration) {
    $trunk = NetworkInterface::factory()->for($this->node)->trunk()->create();

    Server::factory()->for($this->node)->create([
        'network_interface_id' => $trunk->id,
        'vlan_tag' => 42,
    ]);
    Server::factory()->for($this->node)->create([
        'network_interface_id' => $trunk->id,
        'vlan_tag' => null,
    ]);

    $rerunMigration();

    expect(Vlan::query()->pluck('tag')->all())->toBe([42]);
});
