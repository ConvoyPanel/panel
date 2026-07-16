<?php

namespace App\Data\Server\Proxmox\GuestAgent;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

class GuestAgentUserData extends Data
{
    public function __construct(
        public string $user,
        public ?string $domain,
        public ?CarbonImmutable $loginTime,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $loginTime = Arr::get($raw, 'login-time');

        return new self(
            user: Arr::get($raw, 'user', ''),
            domain: Arr::get($raw, 'domain'),
            loginTime: $loginTime ? CarbonImmutable::createFromTimestamp((int) $loginTime) : null,
        );
    }
}
