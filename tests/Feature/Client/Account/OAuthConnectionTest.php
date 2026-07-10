<?php

use App\Models\User;

it('lists only the authenticated user oauth connections', function () {
    config(['oauth.providers.google.label' => 'Google']);
    $user = User::factory()->create();
    $user->oauthConnections()->create([
        'provider' => 'google',
        'provider_id' => '1',
        'email' => 'me@example.com',
    ]);

    $other = User::factory()->create();
    $other->oauthConnections()->create(['provider' => 'github', 'provider_id' => '2']);

    $this->actingAs($user)
        ->getJson('/api/client/account/oauth-connections')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.provider', 'google')
        ->assertJsonPath('data.0.label', 'Google')
        ->assertJsonPath('data.0.email', 'me@example.com');
});

it('unlinks a connection the user owns', function () {
    $user = User::factory()->create();
    $connection = $user->oauthConnections()->create(['provider' => 'google', 'provider_id' => '1']);

    $this->actingAs($user)
        ->deleteJson("/api/client/account/oauth-connections/{$connection->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('oauth_connections', ['id' => $connection->id]);
});

it('404s when unlinking someone else connection', function () {
    $owner = User::factory()->create();
    $connection = $owner->oauthConnections()->create(['provider' => 'google', 'provider_id' => '1']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->deleteJson("/api/client/account/oauth-connections/{$connection->id}")
        ->assertNotFound();

    $this->assertDatabaseHas('oauth_connections', ['id' => $connection->id]);
});
