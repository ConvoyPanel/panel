<?php

use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;

beforeEach(function () {
    $this->node = Node::factory()->create();
});

$serverOn = function (NetworkInterface $interface, ?int $tag) {
    return Server::factory()->for($interface->node)->create([
        'network_interface_id' => $interface->id,
        'vlan_tag' => $tag,
    ]);
};

it('files a server with no tag of its own under the bridge default', function () use ($serverOn) {
    $interface = NetworkInterface::factory()->for($this->node)->trunk(100)->create();

    $serverOn($interface, null);
    $serverOn($interface, null);
    $serverOn($interface, 205);

    // Grouping on servers.vlan_tag alone would report two "untagged" servers
    // while Proxmox has them on tag 100.
    expect($interface->vlanUsage()->all())->toBe([100 => 2, 205 => 1]);
});

it('leaves untagged servers out of a pure trunk', function () use ($serverOn) {
    $interface = NetworkInterface::factory()->for($this->node)->trunk()->create();

    $serverOn($interface, null);
    $serverOn($interface, 310);

    expect($interface->vlanUsage()->all())->toBe([310 => 1]);
});

it('reports no usage for a bridge that is not vlan aware', function () use ($serverOn) {
    $interface = NetworkInterface::factory()->for($this->node)->create();

    $serverOn($interface, null);

    // A non-aware bridge forces a null tag on every server on it, so there is
    // no VLAN membership to report even if a stray tag survived in the column.
    expect($interface->vlanUsage()->all())->toBe([]);
});

it('ignores servers on other bridges', function () use ($serverOn) {
    $interface = NetworkInterface::factory()->for($this->node)->trunk(100)->create();
    $other = NetworkInterface::factory()->for($this->node)->trunk(100)->create();

    $serverOn($interface, null);
    $serverOn($other, null);
    $serverOn($other, null);

    expect($interface->vlanUsage()->all())->toBe([100 => 1]);
});
