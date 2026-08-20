<?php

namespace Database\Factories;

use App\Models\AnchorEnrollmentKey;
use App\Services\Anchor\AnchorEnrollmentKeyService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AnchorEnrollmentKey> */
class AnchorEnrollmentKeyFactory extends Factory
{
    /**
     * The plaintext of the last key this factory built, so a test can present
     * the token it just created without reaching for the hash.
     */
    public static ?string $lastToken = null;

    public function definition(): array
    {
        $token = AnchorEnrollmentKeyService::TOKEN_PREFIX.Str::random(64);
        self::$lastToken = $token;

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->words(2, true),
            'token_hash' => hash('sha256', $token),
            'mode' => null,
            'max_uses' => 1,
            'uses' => 0,
            'expires_at' => now()->addMinutes(AnchorEnrollmentKeyService::DEFAULT_TTL_MINUTES),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinute()]);
    }

    public function exhausted(): static
    {
        return $this->state(fn () => ['max_uses' => 1, 'uses' => 1]);
    }

    /** A key with no expiry and no use limit -- the machine-image shape. */
    public function unlimited(): static
    {
        return $this->state(fn () => ['expires_at' => null, 'max_uses' => null]);
    }
}
