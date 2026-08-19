<?php

use App\Models\Backup;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Models\User;
use App\Services\Backups\BackupDeletionService;

beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();
    $this->storage = Storage::factory()->create();
    $node->storages()->attach($this->storage);
    $this->server = Server::factory()->for($node)->for($this->admin)->create();
});

it('deletes a backup without going through its server', function () {
    // The point of the route: an operator clearing a full disk should not have to
    // find the owning server first.
    $backup = Backup::factory()->for($this->server)->create([
        'storage_id' => $this->storage->id,
        'is_locked' => false,
    ]);

    $deletion = Mockery::mock(BackupDeletionService::class);
    $deletion->shouldReceive('handle')->once()->with(Mockery::on(
        fn (Backup $given) => $given->is($backup),
    ));
    app()->instance(BackupDeletionService::class, $deletion);

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/backups/{$backup->uuid}")
        ->assertNoContent();
});

it('refuses to delete a locked backup', function () {
    $backup = Backup::factory()->for($this->server)->create([
        'storage_id' => $this->storage->id,
        'is_locked' => true,
    ]);

    $deletion = Mockery::mock(BackupDeletionService::class);
    $deletion->shouldNotReceive('handle');
    app()->instance(BackupDeletionService::class, $deletion);

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/backups/{$backup->uuid}")
        ->assertStatus(422)
        ->assertJsonValidationErrors('backup');
});

it('requires an admin user', function () {
    $backup = Backup::factory()->for($this->server)->create([
        'storage_id' => $this->storage->id,
    ]);

    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->deleteJson("/api/admin/backups/{$backup->uuid}")
        ->assertForbidden();
});
