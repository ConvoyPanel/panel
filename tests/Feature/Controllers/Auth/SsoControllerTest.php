<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/** Build a signed SSO deep link for the given user, mirroring Admin\UserController::getSSOToken. */
function ssoLink(User $user, ?string $nonce = null, string $expires = '+60 seconds'): string
{
    return URL::temporarySignedRoute('auth.sso.consume', new DateTimeImmutable($expires), [
        'uuid' => $user->uuid,
        'nonce' => $nonce ?? Str::random(40),
    ]);
}

it('logs the target user in when a valid signed link is consumed', function () {
    $user = User::factory()->create();

    $this->get(ssoLink($user))
        ->assertRedirect(route('index'));

    $this->assertAuthenticatedAs($user);
});

it('rejects a link whose signature has been tampered with', function () {
    $user = User::factory()->create();

    // Flip the trailing signature character so the HMAC no longer matches.
    $link = ssoLink($user);
    $tampered = substr($link, 0, -1).($link[-1] === 'a' ? 'b' : 'a');

    $this->get($tampered)->assertForbidden();
    $this->assertGuest();
});

it('rejects an expired link', function () {
    $user = User::factory()->create();

    $this->get(ssoLink($user, expires: '-1 second'))->assertForbidden();
    $this->assertGuest();
});

it('rejects a replayed link (single use)', function () {
    $user = User::factory()->create();
    $link = ssoLink($user);

    // First use logs in; log back out so the second use is judged on the nonce alone.
    $this->get($link)->assertRedirect();
    $this->post('/api/auth/logout');

    $this->get($link)->assertStatus(401);
    $this->assertGuest();
});

it('rejects a signed link referencing an unknown user', function () {
    $ghost = User::factory()->make(['uuid' => Str::uuid()->toString()]);

    $this->get(ssoLink($ghost))->assertStatus(401);
    $this->assertGuest();
});
