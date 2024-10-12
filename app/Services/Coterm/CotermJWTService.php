<?php

namespace Convoy\Services\Coterm;

use Carbon\CarbonImmutable;
use Convoy\Enums\Server\ConsoleType;
use Convoy\Models\Coterm;
use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Services\Api\JWTService;
use Lcobucci\JWT\Token\Plain;
use Webmozart\Assert\Assert;

class CotermJWTService
{
    public function __construct(private JWTService $JWTService)
    {
    }

    public function handle(Server $server, User $user, ConsoleType $consoleType): Plain
    {
        Assert::isInstanceOf(
            $server->node->coterm, Coterm::class,
            'The server\'s node does not have a Coterm instance.',
        );

        $token = $this->JWTService
            ->setExpiresAt(CarbonImmutable::now()->addMinute())
            ->setUser($user)
            ->setClaims([
                'server_uuid' => $server->uuid,
                'console_type' => $consoleType->value,
            ])
            ->handle(
                $server->node->coterm->token, $server->node->getCotermConnectionAddress(),
                $user->uuid.$server->uuid,
            );

        return $token;
    }
}
