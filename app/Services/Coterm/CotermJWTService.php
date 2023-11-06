<?php

namespace Convoy\Services\Coterm;

use Convoy\Models\User;
use Convoy\Models\Server;
use Carbon\CarbonImmutable;
use Webmozart\Assert\Assert;
use Lcobucci\JWT\Token\Plain;
use Convoy\Services\Api\JWTService;
use Convoy\Enums\Server\ConsoleType;

class CotermJWTService
{
    public function __construct(private JWTService $JWTService) {}

    public function handle(Server $server, User $user, ConsoleType $consoleType): Plain
    {
        Assert::notEmpty($server->node->coterm_fqdn, 'Coterm FQDN isn\'t defined. Is Coterm enabled?');
        Assert::notEmpty($server->node->coterm_token, 'Coterm token isn\'t defined. Is Coterm enabled?');

        $token = $this->JWTService
                ->setExpiresAt(CarbonImmutable::now()->addSeconds(30))
                ->setUser($user)
                ->setClaims([
                    'server_uuid' => $server->uuid,
                    'console_type' => $consoleType->value,
                ])
            ->handle($server->node->coterm_token, $server->node->getCotermConnectionAddress(), $user->uuid . $server->uuid);

        return $token;
    }
}