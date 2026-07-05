<?php

use App\Models\User;
use App\Services\Api\JWTService;

/** Mint a consume-token JWT carrying the given user, signed with $signingKey. */
function consumeToken(User $user, string $signingKey): string
{
    return app(JWTService::class)
        ->issue(
            signingKey: $signingKey,
            audience: config('app.url'),
            identifier: 'test',
            claims: ['user_uuid' => $user->uuid],
            expiresAt: new DateTimeImmutable('+5 minutes'),
        )
        ->toString();
}

it('rejects a consume-token JWT that is not signed with our key', function () {
    $user = User::factory()->create();

    // A forged token: valid structure and claims, but signed with a key we
    // don't control (HMAC-SHA256 requires >=256-bit keys). Without signature
    // validation this would log the attacker in as $user.
    $token = consumeToken($user, str_repeat('attacker-key-', 4));

    $response = $this->get('/api/auth/consume-token?token='.$token);

    $response->assertStatus(401);
    $this->assertGuest();
});

it('accepts a consume-token JWT signed with our key and logs the user in', function () {
    $user = User::factory()->create();

    $token = consumeToken($user, config('app.key'));

    $response = $this->get('/api/auth/consume-token?token='.$token);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($user);
});
