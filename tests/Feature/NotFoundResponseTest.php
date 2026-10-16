<?php

use Convoy\Models\User;

// A deleted record used to come back as "No query results for model
// [Convoy\Models\Server]", which the panel showed to users as-is.
it('names a missing server in plain words', function () {
    $this->actingAs(User::factory()->create())
         ->getJson('/api/client/servers/deadbeef')
         ->assertNotFound()
         ->assertExactJson(['message' => 'This server could not be found. It may have been deleted.']);
});

it('names a missing node in plain words', function () {
    $this->actingAs(User::factory()->create(['root_admin' => true]))
         ->getJson('/api/admin/nodes/999999999')
         ->assertNotFound()
         ->assertJsonPath('message', 'This node could not be found. It may have been deleted.');
});
