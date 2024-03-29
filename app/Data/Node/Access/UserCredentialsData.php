<?php

namespace Convoy\Data\Node\Access;

use Spatie\LaravelData\Data;
use Convoy\Enums\Node\Access\RealmType;

class UserCredentialsData extends Data
{
    public function __construct(
        public string    $username,
        public RealmType $realm_type,
        public string    $ticket,
        public string    $csrf_token,
    )
    {

    }

    public static function fromRaw(array $raw): UserCredentialsData
    {
        return new self(...[
            'username' => explode('@', $raw['username'])[0],
            'realm_type' => RealmType::from(explode('@', $raw['username'])[1]),
            'ticket' => $raw['ticket'],
            'csrf_token' => $raw['CSRFPreventionToken'],
        ]);
    }
}