<?php

use App\Enums\Api\ApiKeyType;
use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\PersonalAccessToken;
use App\Models\SystemActor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;

describe('expiry', function () {
    it('mints an application token that expires', function () {
        $admin = User::factory()->create(['root_admin' => true]);
        $expires = now()->addDays(30)->startOfSecond();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/tokens', ['name' => 'ci', 'expires_at' => $expires->toIso8601String()])
            ->assertSuccessful();

        expect(CarbonImmutable::parse($response->json('data.expiresAt'))->equalTo($expires))->toBeTrue();

        expect(PersonalAccessToken::query()->latest('id')->first()->expires_at->equalTo($expires))->toBeTrue();
    });

    it('refuses an expiry in the past', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(confirmedSession())
            ->postJson('/api/client/account/api-keys', ['name' => 'cli', 'expires_at' => now()->subDay()->toIso8601String()])
            ->assertJsonValidationErrorFor('expires_at');
    });

    it('stops authenticating an expired token', function () {
        $user = User::factory()->create();
        $plain = $this->actingAs($user)->withSession(confirmedSession())
            ->postJson('/api/client/account/api-keys', ['name' => 'cli', 'expires_at' => now()->addHour()->toIso8601String()])
            ->assertSuccessful()
            ->json('data.plainTextToken');

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->travel(2)->hours();

        $this->withToken($plain)->getJson('/api/client/servers')->assertUnauthorized();
    });
});

describe('the tokens:create command', function () {
    it('mints a scoped, restricted, expiring token and prints only the token', function () {
        $exit = Artisan::call('tokens:create', [
            '--name' => 'terraform',
            '--ability' => ['servers:write', 'nodes:read'],
            '--allow' => ['10.0.0.0/8'],
            '--expires' => '+90 days',
            '--plain' => true,
        ]);

        $plain = trim(Artisan::output());
        $token = PersonalAccessToken::findToken($plain);

        expect($exit)->toBe(0)
            ->and($token)->not->toBeNull()
            ->and($token->type)->toBe(ApiKeyType::APPLICATION)
            ->and($token->tokenable)->toBeInstanceOf(SystemActor::class)
            ->and($token->abilities)->toBe(['servers:write', 'nodes:read'])
            ->and($token->allowed_networks)->toBe(['10.0.0.0/8'])
            ->and($token->created_by)->toBeNull()
            ->and($token->expires_at->isFuture())->toBeTrue();

        $log = AuditLog::query()->where('event', AuditEvent::ADMIN_TOKEN_CREATED->value)->sole();
        expect($log->actor)->toBeInstanceOf(SystemActor::class)
            ->and($log->properties['via'])->toBe('cli');
    });

    it('mints a token that reaches the application api', function () {
        Artisan::call('tokens:create', ['--name' => 'bootstrap', '--plain' => true]);

        $this->withToken(trim(Artisan::output()))
            ->getJson('/api/application/locations')
            ->assertOk();
    });

    it('refuses an unknown ability', function () {
        expect(Artisan::call('tokens:create', ['--name' => 'x', '--ability' => ['everything']]))->toBe(1)
            ->and(PersonalAccessToken::query()->count())->toBe(0);
    });

    it('needs a name', function () {
        expect(Artisan::call('tokens:create'))->toBe(1);
    });
});

describe('personal tokens on the client api', function () {
    it('lets a token scoped to servers ask who it belongs to', function () {
        $user = User::factory()->create();
        $token = $user->createToken('cli', ApiKeyType::ACCOUNT, ['servers:read'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/client/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    });

    it('does not set session cookies on a token request', function () {
        $user = User::factory()->create();
        $token = $user->createToken('cli', ApiKeyType::ACCOUNT, ['servers:read'])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/client/servers')->assertOk();

        $names = collect($response->headers->getCookies())->map->getName()->all();
        expect($names)->not->toContain(config('session.cookie'))
            ->and($names)->not->toContain('XSRF-TOKEN');
    });

    it('still sets them for the panel session', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/client/servers')->assertOk();

        expect(collect($response->headers->getCookies())->map->getName()->all())
            ->toContain(config('session.cookie'));
    });
});
