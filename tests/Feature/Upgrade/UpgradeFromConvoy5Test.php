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
