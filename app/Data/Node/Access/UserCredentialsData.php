<?php

namespace App\Data\Node\Access;

use App\Enums\Node\Access\RealmType;
use Spatie\LaravelData\Data;

class UserCredentialsData extends Data
{
    public function __construct(
        public string $username,
        public RealmType $realmType,
        public string $ticket,
        public string $csrfToken,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(...[
            'username' => explode('@', $raw['username'])[0],
            'realmType' => RealmType::from(explode('@', $raw['username'])[1]),
            'ticket' => $raw['ticket'],
            'csrfToken' => $raw['CSRFPreventionToken'],
        ]);
    }
}
