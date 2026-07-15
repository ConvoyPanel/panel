<?php

use App\Enums\Api\ApiKeyType;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\Api\CreateAccountTokenService;
use App\Services\Api\CreateApplicationTokenService;
use Illuminate\Support\Facades\Auth;

/** @param list<string> $allowedNetworks */
function networkRestrictedToken(array $allowedNetworks): string
{
    $admin = User::factory()->create(['root_admin' => true]);

    return app(CreateApplicationTokenService::class)
        ->handle($admin, 'integration', ['locations:read'], $allowedNetworks)
        ->plainTextToken;
}

function callApplicationFrom(string $token, string $ip)
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/application/locations');
}

it('keeps an empty application-token allowlist unrestricted', function () {
    $token = networkRestrictedToken([]);

    callApplicationFrom($token, '198.51.100.10')->assertOk();
    callApplicationFrom($token, '2001:db8::10')->assertOk();
});

it('allows exact addresses and CIDR ranges from either IP family', function (array $rules, string $ip) {
    callApplicationFrom(networkRestrictedToken($rules), $ip)->assertOk();
})->with([
    'exact IPv4' => [['198.51.100.10'], '198.51.100.10'],
    'IPv4 CIDR' => [['198.51.100.0/24'], '198.51.100.42'],
    'exact IPv6' => [['2001:db8::10'], '2001:db8::10'],
    'IPv6 CIDR' => [['2001:db8::/48'], '2001:db8:0:1::42'],
    'one of multiple rules' => [['192.0.2.10', '198.51.100.0/24'], '198.51.100.42'],
]);

it('returns a stable forbidden error outside the token allowlist', function () {
    $token = networkRestrictedToken(['198.51.100.0/24', '2001:db8::/48']);

    callApplicationFrom($token, '203.0.113.10')
        ->assertForbidden()
        ->assertExactJson([
            'message' => 'This token cannot be used from this IP address.',
            'code' => 'token_ip_not_allowed',
        ]);
});

it('does not trust a forwarded client address from an untrusted proxy', function () {
    $token = networkRestrictedToken(['198.51.100.10']);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeader('X-Forwarded-For', '198.51.100.10')
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/application/locations')
        ->assertForbidden();
});

it('uses the forwarded client address when the immediate proxy is trusted', function () {
    config()->set('trustedproxy.proxies', ['203.0.113.10']);
    $token = networkRestrictedToken(['198.51.100.10']);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeader('X-Forwarded-For', '198.51.100.10')
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/application/locations')
        ->assertOk();
});

it('stores normalized network restrictions when creating a token', function () {
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)->postJson('/api/admin/tokens', [
        'name' => 'deploy-bot',
        'allowed_networks' => [' 198.51.100.10 ', '2001:db8::/48', '198.51.100.10'],
    ])->assertSuccessful()
        ->assertJsonPath('data.allowedNetworks', ['198.51.100.10', '2001:db8::/48']);

    expect(PersonalAccessToken::query()->latest('id')->firstOrFail()->allowed_networks)
        ->toBe(['198.51.100.10', '2001:db8::/48']);
});

it('rejects invalid addresses and CIDR ranges when creating a token', function (string $rule) {
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)->postJson('/api/admin/tokens', [
        'name' => 'bad-network',
        'allowed_networks' => [$rule],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('allowed_networks.0');
})->with([
    'hostname' => 'api.example.test',
    'invalid IPv4' => '999.51.100.10',
    'IPv4 prefix too large' => '198.51.100.0/33',
    'IPv6 prefix too large' => '2001:db8::/129',
    'negative prefix' => '198.51.100.0/-1',
]);

it('applies edited restrictions immediately and can clear them', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $newToken = app(CreateApplicationTokenService::class)
        ->handle($admin, 'rotating-integration', ['locations:read'], ['198.51.100.10']);
    $token = $newToken->accessToken;

    expect($token)->toBeInstanceOf(PersonalAccessToken::class);
    callApplicationFrom($newToken->plainTextToken, '198.51.100.10')->assertOk();

    $this->actingAs($admin)->putJson("/api/admin/tokens/{$token->id}", [
        'allowed_networks' => ['203.0.113.0/24'],
    ])->assertOk()
        ->assertJsonPath('data.allowedNetworks', ['203.0.113.0/24']);

    // The next request must resolve the bearer token, not the admin session used for the edit.
    Auth::guard('web')->logout();
    Auth::forgetGuards();
    callApplicationFrom($newToken->plainTextToken, '198.51.100.10')->assertForbidden();
    callApplicationFrom($newToken->plainTextToken, '203.0.113.42')->assertOk();

    $this->actingAs($admin)->putJson("/api/admin/tokens/{$token->id}", [
        'allowed_networks' => [],
    ])->assertOk()
        ->assertJsonPath('data.allowedNetworks', []);

    Auth::guard('web')->logout();
    Auth::forgetGuards();
    callApplicationFrom($newToken->plainTextToken, '192.0.2.200')->assertOk();
});

it('does not allow the admin token endpoint to edit an account PAT', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $accountToken = app(CreateAccountTokenService::class)->handle($admin, 'account');

    expect($accountToken->accessToken)
        ->toBeInstanceOf(PersonalAccessToken::class)
        ->type->toBe(ApiKeyType::ACCOUNT);

    $this->actingAs($admin)->putJson("/api/admin/tokens/{$accountToken->accessToken->id}", [
        'allowed_networks' => ['198.51.100.10'],
    ])->assertNotFound();
});

it('does not apply application-token network restrictions to account PATs', function () {
    $user = User::factory()->create();
    $accountToken = app(CreateAccountTokenService::class)
        ->handle($user, 'account', ['servers:read']);
    $accountToken->accessToken->update(['allowed_networks' => ['198.51.100.10']]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeader('Authorization', "Bearer {$accountToken->plainTextToken}")
        ->getJson('/api/client/servers')
        ->assertOk();
});
