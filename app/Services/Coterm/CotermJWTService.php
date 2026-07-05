<?php

namespace App\Services\Coterm;

use App\Enums\Server\ConsoleType;
use App\Models\Coterm;
use App\Models\Server;
use App\Models\User;
use App\Services\Api\JWTService;
use Carbon\CarbonImmutable;
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
            $server->node->coterm,
            Coterm::class,
            'The server\'s node does not have a Coterm instance.',
        );

        return $this->JWTService->issue(
            signingKey: $server->node->coterm->token,
            audience: $server->node->getCotermConnectionAddress(),
            identifier: $user->uuid.$server->uuid,
            claims: [
                'user_uuid' => $user->uuid,
                'server_uuid' => $server->uuid,
                'console_type' => $consoleType->value,
            ],
            expiresAt: CarbonImmutable::now()->addMinute(),
        );
    }
}
