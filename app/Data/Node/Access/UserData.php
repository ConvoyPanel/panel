<?php

namespace Convoy\Data\Node\Access;

use Carbon\Carbon;
use Convoy\Enums\Node\Access\RealmType;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public string $username,
        public ?string $email,
        #[WithCast(EnumCast::class)]
        public RealmType $realm_type,
        public bool $enabled,
        public ?Carbon $expires_at,
    ) {
    }

    public static function fromRaw(array $raw): self
    {
        return new self(...[
            'username' => explode('@', $raw['userid'])[0],
            'email' => Arr::get($raw, 'email'),
            'realm_type' => RealmType::from($raw['realm-type']),
            'enabled' => (bool) $raw['enable'],
            'expires_at' => Arr::get($raw, 'expire') ? Carbon::createFromTimestamp($raw['expire']) : null,
        ]);
    }
}
