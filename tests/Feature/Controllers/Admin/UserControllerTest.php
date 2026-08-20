<?php

use App\Enums\Api\ApiKeyType;
use App\Models\SessionRecord;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

it('revokes API tokens when an admin is demoted', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create(['root_admin' => true]);
    $target->createToken('test', ApiKeyType::ACCOUNT);

    expect($target->tokens()->count())->toBe(1);

    $response = $this->actingAs($admin)->putJson("/api/admin/users/{$target->id}", [
        'name' => $target->name,
        'email' => $target->email,
        'root_admin' => false,
    ]);

    $response->assertOk();
    expect($target->fresh()->tokens()->count())->toBe(0);
});

it('keeps API tokens when an update does not demote the user', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create(['root_admin' => true]);
    $target->createToken('test', ApiKeyType::ACCOUNT);

    $this->actingAs($admin)->putJson("/api/admin/users/{$target->id}", [
        'name' => 'Renamed',
        'email' => $target->email,
        'root_admin' => true,
    ])->assertOk();

    expect($target->fresh()->tokens()->count())->toBe(1);
});

it('deletes users through the deletion service cleanup path', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create();
    $target->createToken('test', ApiKeyType::ACCOUNT);

    SessionRecord::query()->create([
        'session_id' => 'target-device',
        'user_id' => $target->id,
        'ip_address' => '203.0.113.7',
        'user_agent' => 'Mozilla/5.0',
        'last_active_at' => now(),
    ]);

    $handler = app('session')->getHandler();
    $handler->write('target-device', 'live');

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$target->id}")
        ->assertNoContent();

    expect(User::query()->whereKey($target->id)->exists())->toBeFalse()
        ->and($target->tokens()->count())->toBe(0)
        ->and(SessionRecord::query()->where('user_id', $target->id)->exists())->toBeFalse()
        ->and($handler->read('target-device'))->toBe('');
});

it('mints a single-use signed SSO link for a user', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create();

    $response = $this->actingAs($admin)
        ->postJson("/api/admin/users/{$target->id}/generate-sso-token")
        ->assertSuccessful();

    $response->assertJsonPath('data.userId', $target->id);
    $link = $response->json('data.link');
    expect($link)->toContain('/api/auth/sso/'.$target->uuid)
        ->and($link)->toContain('signature=');

    // The freshly minted link is honoured by the consume endpoint (logs the target in). Drop the
    // admin session first so the `guest`-only consume route runs (a fresh browser has no session).
    auth()->logout();

    $this->get($link)->assertRedirect(route('index'));
    $this->assertAuthenticatedAs($target);
});

/** A passphrase: no uppercase, digits or symbols, and stronger than anything that has them. */
const ADMIN_SET_PASSPHRASE = 'meandering copper thistle vault';

/**
 * Default the breach check to "not found", i.e. not breached. Registered at call time so a fake a
 * test set up beforehand is matched first and wins (Http::fake is first-match-wins). Without it the
 * suite-wide preventStrayRequests() would throw, `uncompromised()` would swallow that and fail
 * open, and these would pass because the check *errored* rather than because it succeeded.
 */
function allowBreachCheck(): void
{
    Http::fake(['https://api.pwnedpasswords.com/*' => Http::response('', 200)]);
}

it('creates a user under the panel password policy', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    allowBreachCheck();

    $response = $this->actingAs($admin)->postJson('/api/admin/users', [
        'name' => 'Wren Alcott',
        'email' => 'wren@example.com',
        'password' => ADMIN_SET_PASSPHRASE,
        'root_admin' => false,
    ])->assertSuccessful();

    $response->assertJsonPath('data.email', 'wren@example.com')
        ->assertJsonPath('data.rootAdmin', false)
        ->assertJsonPath('data.serversCount', 0);

    $created = User::query()->where('email', 'wren@example.com')->sole();
    expect(Hash::check(ADMIN_SET_PASSPHRASE, $created->password))->toBeTrue();
});

it('holds an admin-set password to the same policy as a self-chosen one', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    allowBreachCheck();

    // Eight characters cleared Laravel's `Password::defaults()`, which is what these endpoints
    // used to run on while the account screen required twelve.
    $this->actingAs($admin)->postJson('/api/admin/users', [
        'name' => 'Wren Alcott',
        'email' => 'wren@example.com',
        'password' => 'Sh0rt!pw',
        'root_admin' => false,
    ])->assertJsonValidationErrors('password');

    // bcrypt hashes at most 72 bytes and ignores the rest, so a longer one would be accepted while
    // only its leading 72 bytes ever authenticated.
    $this->actingAs($admin)->postJson('/api/admin/users', [
        'name' => 'Wren Alcott',
        'email' => 'wren@example.com',
        'password' => str_repeat('a', 73),
        'root_admin' => false,
    ])->assertJsonValidationErrors('password');

    expect(User::query()->where('email', 'wren@example.com')->exists())->toBeFalse();
});

it('leaves the password alone when the field is left blank', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create(['password' => Hash::make('Password123!')]);
    $before = $target->password;

    // What the edit form posts for a password the admin never touched.
    $this->actingAs($admin)->putJson("/api/admin/users/{$target->id}", [
        'name' => 'Renamed',
        'email' => $target->email,
        'root_admin' => false,
        'password' => '',
    ])->assertOk();

    expect($target->fresh()->password)->toBe($before);
});

it('resets another account password when one is supplied', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $target = User::factory()->create(['password' => Hash::make('Password123!')]);
    allowBreachCheck();

    $this->actingAs($admin)->putJson("/api/admin/users/{$target->id}", [
        'name' => $target->name,
        'email' => $target->email,
        'root_admin' => false,
        'password' => ADMIN_SET_PASSPHRASE,
    ])->assertOk();

    expect(Hash::check(ADMIN_SET_PASSPHRASE, $target->fresh()->password))->toBeTrue();
});

it('refuses to let an admin demote or delete their own account', function () {
    $admin = User::factory()->create(['root_admin' => true]);

    // Both are one-way doors: the screen you would undo them from is the one you just lost.
    $this->actingAs($admin)->putJson("/api/admin/users/{$admin->id}", [
        'name' => $admin->name,
        'email' => $admin->email,
        'root_admin' => false,
    ])->assertBadRequest();

    $this->actingAs($admin)->deleteJson("/api/admin/users/{$admin->id}")
        ->assertBadRequest();

    expect($admin->fresh()->root_admin)->toBeTrue()
        ->and(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

it('searches users by name, email fragment and id', function () {
    $admin = User::factory()->create(['root_admin' => true, 'name' => 'Operator', 'email' => 'ops@example.com']);
    $target = User::factory()->create(['name' => 'Wren Alcott', 'email' => 'wren@contoso.test']);

    $emails = fn (string $term) => collect(
        $this->actingAs($admin)->getJson('/api/admin/users?filter[*]='.$term)
            ->assertOk()
            ->json('items')
    )->pluck('email')->all();

    // `id` is a bigint, and on Postgres comparing it against a term that is not a number is an
    // error rather than a miss — searching by name used to 500 rather than match.
    expect($emails('Alcott'))->toBe(['wren@contoso.test'])
        ->and($emails('contoso'))->toBe(['wren@contoso.test'])
        ->and($emails((string) $target->id))->toBe(['wren@contoso.test'])
        ->and($emails('nobody'))->toBe([]);
});

it('sorts the list by the columns the admin table offers', function () {
    $admin = User::factory()->create(['root_admin' => true, 'name' => 'Beatrix']);
    $other = User::factory()->create(['name' => 'Aurelio']);

    // The table sends the column it sorted by, which is named after the response property.
    $names = fn (string $sort) => collect(
        $this->actingAs($admin)->getJson('/api/admin/users?sort='.$sort)->assertOk()->json('items')
    )->pluck('name')->all();

    expect($names('name'))->toBe(['Aurelio', 'Beatrix'])
        ->and($names('-name'))->toBe(['Beatrix', 'Aurelio'])
        ->and($names('serversCount'))->toHaveCount(2)
        ->and($names('createdAt'))->toHaveCount(2)
        ->and($names('rootAdmin'))->toHaveCount(2);

    expect($other->fresh())->not->toBeNull();
});
