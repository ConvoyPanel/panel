<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

/** Enable a provider the way an operator would (config/oauth.php flag + config/services.php creds). */
function enableProvider(string $provider = 'google'): void
{
    config([
        "oauth.providers.{$provider}.enabled" => true,
        "oauth.providers.{$provider}.label" => ucfirst($provider),
        "services.{$provider}.client_id" => 'client-id',
        "services.{$provider}.client_secret" => 'client-secret',
    ]);
}

/** Build the Socialite user the provider would hand back. */
function socialiteUser(string $id = '1001', string $email = 'jane@example.com', bool $verified = true): SocialiteUser
{
    $user = new SocialiteUser;
    $user->id = $id;
    $user->name = 'Jane Doe';
    $user->email = $email;
    $user->user = ['email_verified' => $verified];

    return $user;
}

/** Stub Socialite's driver so `->user()` returns our fake (or throws). */
function fakeSocialiteCallback(SocialiteUser|Throwable $result, string $provider = 'google'): void
{
    $driver = Mockery::mock(AbstractProvider::class);

    if ($result instanceof Throwable) {
        $driver->shouldReceive('user')->andThrow($result);
    } else {
        $driver->shouldReceive('user')->andReturn($result);
    }

    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
}

it('redirects an unknown/disabled provider to a 404', function () {
    // google not enabled in config for this test
    $this->get('/api/auth/oauth/google/redirect')->assertNotFound();
});

it('starts the provider handshake and remembers the intended path', function () {
    enableProvider();

    $driver = Mockery::mock(AbstractProvider::class);
    $driver->shouldReceive('redirect')->andReturn(redirect('https://accounts.example.test/authorize'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

    $this->get('/api/auth/oauth/google/redirect?intended=/servers')
        ->assertRedirect('https://accounts.example.test/authorize');

    expect(session('oauth.intended'))->toBe('/servers');
});

it('ignores an absolute intended URL (open-redirect guard)', function () {
    enableProvider();

    $driver = Mockery::mock(AbstractProvider::class);
    $driver->shouldReceive('redirect')->andReturn(redirect('https://accounts.example.test/authorize'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

    $this->get('/api/auth/oauth/google/redirect?intended=https://evil.test')->assertRedirect();

    expect(session('oauth.intended'))->toBe('/');
});

it('logs in through an existing connection', function () {
    enableProvider();
    $user = User::factory()->create();
    $user->oauthConnections()->create([
        'provider' => 'google',
        'provider_id' => '1001',
    ]);

    fakeSocialiteCallback(socialiteUser());

    $this->get('/api/auth/oauth/google/callback')->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

it('links to an existing user by verified email on first sign-in', function () {
    enableProvider();
    config(['oauth.link_by_verified_email' => true]);
    $user = User::factory()->create(['email' => 'jane@example.com']);

    fakeSocialiteCallback(socialiteUser(email: 'jane@example.com', verified: true));

    $this->get('/api/auth/oauth/google/callback')->assertRedirect('/');
    $this->assertAuthenticatedAs($user);

    $this->assertDatabaseHas('oauth_connections', [
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => '1001',
    ]);
});

it('does not link by email when the provider email is unverified', function () {
    enableProvider();
    config(['oauth.link_by_verified_email' => true, 'oauth.registration' => false]);
    User::factory()->create(['email' => 'jane@example.com']);

    fakeSocialiteCallback(socialiteUser(email: 'jane@example.com', verified: false));

    $this->get('/api/auth/oauth/google/callback')
        ->assertRedirect('/auth/login?oauth_error=oauth_account_not_provisioned');
    $this->assertGuest();
});

it('refuses an unknown identity when registration is disabled', function () {
    enableProvider();
    config(['oauth.registration' => false, 'oauth.link_by_verified_email' => true]);

    fakeSocialiteCallback(socialiteUser(email: 'nobody@example.com'));

    $this->get('/api/auth/oauth/google/callback')
        ->assertRedirect('/auth/login?oauth_error=oauth_account_not_provisioned');
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

it('provisions a new non-admin user when registration is enabled', function () {
    enableProvider();
    config(['oauth.registration' => true]);

    fakeSocialiteCallback(socialiteUser(email: 'new@example.com', verified: true));

    $this->get('/api/auth/oauth/google/callback')->assertRedirect('/');

    $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'root_admin' => false]);
    $user = User::where('email', 'new@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->email_verified_at)->not->toBeNull();
});

it('does not provision from an unverified email even with registration on', function () {
    enableProvider();
    config(['oauth.registration' => true]);

    fakeSocialiteCallback(socialiteUser(email: 'spoof@example.com', verified: false));

    $this->get('/api/auth/oauth/google/callback')
        ->assertRedirect('/auth/login?oauth_error=oauth_account_not_provisioned');
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

it('sends the user back to login on invalid oauth state', function () {
    enableProvider();

    fakeSocialiteCallback(new InvalidStateException);

    $this->get('/api/auth/oauth/google/callback')
        ->assertRedirect('/auth/login?oauth_error=oauth_invalid_state');
    $this->assertGuest();
});

it('links a provider to the currently authenticated user', function () {
    enableProvider();
    $user = User::factory()->create();

    fakeSocialiteCallback(socialiteUser(id: '2002'));

    $this->actingAs($user)
        ->get('/api/auth/oauth/google/callback')
        ->assertRedirect('/security?oauth_linked=google');

    $this->assertDatabaseHas('oauth_connections', [
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => '2002',
    ]);
});

it('conflicts when linking an identity already owned by another user', function () {
    enableProvider();
    $owner = User::factory()->create();
    $owner->oauthConnections()->create(['provider' => 'google', 'provider_id' => '2002']);
    $other = User::factory()->create();

    fakeSocialiteCallback(socialiteUser(id: '2002'));

    $this->actingAs($other)
        ->get('/api/auth/oauth/google/callback')
        ->assertRedirect('/security?oauth_error=oauth_identity_already_linked');

    // Ownership unchanged.
    $this->assertDatabaseHas('oauth_connections', [
        'provider_id' => '2002',
        'user_id' => $owner->id,
    ]);
});
