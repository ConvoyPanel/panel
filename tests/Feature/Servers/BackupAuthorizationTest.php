<?php

use App\Enums\Server\ServerPermission;
use App\Models\Backup;
use App\Models\Server;
use App\Policies\ServerPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Which policy governs the backup endpoints.
 *
 * There used to be a `BackupPolicy` alongside `ServerPolicy`, and it looked load-bearing. It was
 * not: every backup request calls `can(…, $server)` with a **Server**, so Laravel resolves
 * `ServerPolicy` and the other class was never consulted. This suite is the evidence for deleting
 * it, and the guard against the two drifting back apart.
 */
beforeEach(function () {
    fakeProxmox();

    [$this->owner, , , $this->server] = createServerModel();
    $this->backup = Backup::factory()->create(['server_id' => $this->server->id]);
});

it('resolves a Server argument to the server policy', function () {
    expect(Gate::getPolicyFor(Server::class))->toBeInstanceOf(ServerPolicy::class);
});

it('has no policy registered for the backup model', function () {
    // A Backup is never the subject of an authorization check; it is reached through its server.
    expect(Gate::getPolicyFor(Backup::class))->toBeNull();
});

it('answers each backup ability from the server policy alone', function () {
    // The proof: a sub-user is not the owner and is not an admin, so nothing in the old
    // BackupPolicy could ever have returned true for them -- its `before` only admitted those two
    // and its `__call` returned nothing. An *allow* here can only have come from ServerPolicy.
    $creator = subuser($this->server, [ServerPermission::BACKUP_CREATE]);
    $restorer = subuser($this->server, [ServerPermission::BACKUP_RESTORE]);
    $deleter = subuser($this->server, [ServerPermission::BACKUP_DELETE]);

    expect(Gate::forUser($creator)->allows('createBackup', $this->server))->toBeTrue()
        ->and(Gate::forUser($creator)->allows('restoreBackup', $this->server))->toBeFalse()
        ->and(Gate::forUser($restorer)->allows('restoreBackup', $this->server))->toBeTrue()
        ->and(Gate::forUser($restorer)->allows('deleteBackup', $this->server))->toBeFalse()
        ->and(Gate::forUser($deleter)->allows('deleteBackup', $this->server))->toBeTrue()
        ->and(Gate::forUser($deleter)->allows('createBackup', $this->server))->toBeFalse();
});

it('reaches the same answer through the HTTP endpoints', function () {
    $deleter = subuser($this->server, [ServerPermission::BACKUP_DELETE]);

    assertReached($this->actingAs($deleter)->deleteJson(
        "/api/client/servers/{$this->server->uuid}/backups/{$this->backup->uuid}",
    ));

    $creator = subuser($this->server, [ServerPermission::BACKUP_CREATE]);

    $this->actingAs($creator)
        ->postJson("/api/client/servers/{$this->server->uuid}/backups", [
            'name' => 'From a sub-user',
            'mode' => 'snapshot',
            'compression_type' => 'none',
            'is_locked' => false,
        ])
        ->assertSuccessful();
});

it('denies an unmapped ability rather than falling through', function () {
    // `__call` is the catch-all for the twenty mapped abilities. Anything else has to be a denial,
    // or an endpoint added without being classified would be open to every sub-user.
    $anyone = subuser($this->server, ServerPermission::cases());

    expect(Gate::forUser($anyone)->allows('destroyEverything', $this->server))->toBeFalse();
});
