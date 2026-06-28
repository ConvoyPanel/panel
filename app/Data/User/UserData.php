<?php

namespace App\Data\User;

use App\Models\User;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapInputName(SnakeCaseMapper::class)]
class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $rootAdmin,
        public int|Optional $serversCount,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            rootAdmin: (bool) $user->root_admin,
            serversCount: isset($user->servers_count)
                ? (int) $user->servers_count
                : Optional::create(),
        );
    }
}
