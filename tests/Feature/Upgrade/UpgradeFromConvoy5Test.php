<?php

use App\Enums\Api\ApiKeyType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Each test puts rows back the way 5.x left them and runs one migration over them.

function upgradeMigration(string $path): object
{
    return require database_path($path);
}

it('points a 5.x token at its owner under App\\Models', function () {
    $user = User::factory()->create(['root_admin' => true]);
    $token = $user->createToken('billing', ApiKeyType::APPLICATION);

    DB::table('personal_access_tokens')
        ->where('id', $token->accessToken->id)
        ->update(['tokenable_type' => 'Convoy\\Models\\User']);

    upgradeMigration('migrations/2026_11_21_000000_point_tokens_at_app_models.php')->up();

    expect(DB::table('personal_access_tokens')->find($token->accessToken->id)->tokenable_type)
        ->toBe(User::class);

    $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
        ->getJson('/api/application/locations')
        ->assertOk();
});

it('refuses a v4 database that skipped 5.x', function () {
    // A v4 database has run migrations dated after the guard but never 5.x's settings table.
    DB::table('migrations')->where('migration', '2022_12_14_083707_create_settings_table')->delete();

    expect(fn () => upgradeMigration('migrations/2022_12_14_083706_require_convoy_5.php')->up())
        ->toThrow(RuntimeException::class, "Import it into 5.x with 'convoy import-v4'");
});

it('lets a 5.x database and a fresh install through', function () {
    $guard = upgradeMigration('migrations/2022_12_14_083706_require_convoy_5.php');

    // This database has every migration, 5.x's settings table included.
    expect(fn () => $guard->up())->not->toThrow(Throwable::class);

    // A fresh install reaches the guard before any migration dated after it.
    DB::table('migrations')->where('migration', '>', '2022_12_14_083706')->delete();

    expect(fn () => $guard->up())->not->toThrow(Throwable::class);
});
