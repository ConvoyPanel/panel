<?php

namespace App\Data\Auth;

use App\Models\SSOToken;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class SSOTokenData extends Data
{
    public function __construct(
        public int $userId,
        public string $token,
    ) {}

    public static function fromModel(SSOToken $token): self
    {
        return new self(
            userId: $token->user_id,
            token: $token->token,
        );
    }
}
