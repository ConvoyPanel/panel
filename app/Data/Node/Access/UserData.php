<?php

namespace App\Data\Node\Access;

use App\Enums\Node\Access\RealmType;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public string    $username,
        public ?string   $email,
        #[WithCast(EnumCast::class)]
        public RealmType $realmType,
        public bool      $enabled,
        public ?Carbon   $expiresAt,
    ) {
    }

    public static function fromRaw(array $raw): self
    {
        return new self(...[
            'username' => explode('@', $raw['userid'])[0],
            'email' => Arr::get($raw, 'email'),
            'realmType' => RealmType::from($raw['realm-type']),
            'enabled' => (bool) $raw['enable'],
            'expiresAt' => Arr::get($raw, 'expire') ? Carbon::createFromTimestamp($raw['expire']) : null,
        ]);
    }
}
