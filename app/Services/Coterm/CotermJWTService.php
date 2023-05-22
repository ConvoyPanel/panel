<?php

namespace Convoy\Services\Coterm;

use Carbon\CarbonImmutable;
use Convoy\Enums\Server\ConsoleType;
use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Services\Api\JWTService;
use Lcobucci\JWT\Token\Plain;
use Webmozart\Assert\Assert;

class CotermJWTService
{
    public function __construct(private JWTService $jwtService) {}

    public function handle(Server $server, User $user, ConsoleType $consoleType): Plain
    {
        Assert::notEmpty($server->node->coterm_fqdn, 'Coterm FQDN isn\'t defined. Is Coterm enabled?');
        Assert::notEmpty($server->node->coterm_token, 'Coterm token isn\'t defined. Is Coterm enabled?');

        $token = $this->jwtService
                ->setExpiresAt(CarbonImmutable::now()->addSeconds(30))
                ->setUser($user)
                ->setClaims([
                    'server_uuid' => $server->uuid,
                    'console_type' => $consoleType->value,
                ])
            ->handle($server->node->coterm_token, $server->node->getCotermConnectionAddress(), $user->id . $server->uuid);

        return $token;
    }
}