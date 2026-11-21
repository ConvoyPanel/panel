<?php

use App\Enums\Api\ApiKeyType;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

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

it("moves 5.x's announcement dismissals onto each user and drops the table", function () {
    [$alice, $bob, $carol] = User::factory()->count(3)->create();

    Schema::table('users', fn (Blueprint $table) => $table->dropColumn('dismissed_announcements'));
    Schema::create('announcement_dismissals', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('announcement_id', 120);
        $table->timestamps();
        $table->unique(['user_id', 'announcement_id']);
    });
    DB::table('announcement_dismissals')->insert([
        ['user_id' => $alice->id, 'announcement_id' => '2026-10-21-php2shell'],
        ['user_id' => $bob->id, 'announcement_id' => '2026-10-21-php2shell'],
        ['user_id' => $alice->id, 'announcement_id' => '2026-11-13-convoy-6'],
    ]);

    upgradeMigration('migrations/2026_11_21_000001_store_announcement_dismissals_on_users.php')->up();

    $dismissed = fn (User $user) => json_decode(DB::table('users')->find($user->id)->dismissed_announcements);

    expect($dismissed($alice))->toBe(['2026-10-21-php2shell', '2026-11-13-convoy-6'])
        ->and($dismissed($bob))->toBe(['2026-10-21-php2shell'])
        ->and($dismissed($carol))->toBeNull()
        ->and(Schema::hasTable('announcement_dismissals'))->toBeFalse();
});

it("moves 5.x's emailed announcements into settings and drops the table", function () {
    $migrator = app(SettingsMigrator::class);
    $migrator->delete('announcements.notified');

    Schema::create('announcement_notifications', function (Blueprint $table) {
        $table->id();
        $table->string('announcement_id', 120)->unique();
        $table->unsignedInteger('recipients')->default(0);
        $table->timestamps();
    });
    DB::table('announcement_notifications')->insert([
        ['announcement_id' => '2026-10-21-php2shell', 'recipients' => 2],
        ['announcement_id' => '2026-11-13-convoy-6', 'recipients' => 3],
    ]);

    upgradeMigration('settings/2026_11_21_000002_create_announcement_settings.php')->up();

    $payload = DB::table('settings')->where('group', 'announcements')->where('name', 'notified')->value('payload');

    expect(json_decode($payload))->toBe(['2026-10-21-php2shell', '2026-11-13-convoy-6'])
        ->and(Schema::hasTable('announcement_notifications'))->toBeFalse();
});

it('starts a fresh install with nothing emailed', function () {
    $payload = DB::table('settings')->where('group', 'announcements')->where('name', 'notified')->value('payload');

    expect(json_decode($payload))->toBe([]);
});
