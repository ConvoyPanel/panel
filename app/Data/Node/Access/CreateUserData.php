<?php

namespace Convoy\Data\Node\Access;

use Carbon\Carbon;
use Convoy\Enums\Node\Access\RealmType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class CreateUserData extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class)]
        public RealmType $realm_type,
        public bool $enabled,
        #[Min(1), Max(60)]
        public ?string $username = null,
        #[Min(1), Max(64)]
        public ?string $password = null,
        public ?Carbon $expires_at = null,
    ) {
    }
}
