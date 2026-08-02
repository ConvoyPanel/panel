<?php

namespace App\Services\Anchor;

use App\Data\Server\ConsoleSessionData;
use App\Enums\Anchor\AnchorCompatibility;
use App\Enums\Anchor\AnchorMode;
use App\Enums\Server\ConsoleType;
use App\Models\Anchor;
use App\Models\Server;
use App\Models\User;
use App\Services\Api\JWTService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AnchorSessionService
{
    public function __construct(
        private JWTService $jwt,
        private AnchorLivenessService $liveness,
    ) {}

    public function create(Server $server, User $user, ConsoleType $type): ConsoleSessionData
    {
        $agent = $server->node->anchor;

        if ($agent === null) {
            throw new ConflictHttpException('This server does not have an Anchor agent configured.');
        }

        if ($agent->mode !== AnchorMode::AGENT) {
            throw new ConflictHttpException('This server must be assigned to an Anchor agent.');
        }

        $agent->loadMissing('relay');
        $this->ensureCompatible($agent);
        $password = $type === ConsoleType::NOVNC ? Str::random(8) : null;
        $console = [
            'type' => $type === ConsoleType::NOVNC ? 'qemu_vnc' : 'qemu_terminal',
            'vm_id' => $server->vmid,
            ...($password !== null ? ['password' => $password] : []),
        ];
        $expiresAt = CarbonImmutable::now()->addMinute();
        $agentToken = $this->issue(
            anchor: $agent,
            server: $server,
            user: $user,
            console: $console,
            expiresAt: $expiresAt,
        );

        if ($agent->relay !== null) {
            $this->ensureCompatible($agent->relay);
            $token = $this->issue(
                anchor: $agent->relay,
                server: $server,
                user: $user,
                console: $console,
                expiresAt: $expiresAt,
                relay: [
                    'url' => $agent->consoleWebsocketUrl(),
                    'token' => $agentToken,
                ],
            );
            $endpoint = $agent->relay;
        } else {
            $token = $agentToken;
            $endpoint = $agent;
        }

        return new ConsoleSessionData(
            url: $endpoint->consoleWebsocketUrl(),
            token: $token,
            protocol: Anchor::PROTOCOL_VERSION,
            type: $type,
            password: $password,
        );
    }

    /** @param array<string, mixed> $console @param array<string, string>|null $relay */
    private function issue(
        Anchor $anchor,
        Server $server,
        User $user,
        array $console,
        CarbonImmutable $expiresAt,
        ?array $relay = null,
    ): string {
        return $this->jwt->issue(
            signingKey: $anchor->secret,
            audience: $anchor->uuid,
            identifier: $user->uuid.$server->uuid.$anchor->uuid.Str::random(),
            claims: array_filter([
                'protocol' => Anchor::PROTOCOL_VERSION,
                'console' => $console,
                'relay' => $relay,
            ], fn (mixed $value) => $value !== null),
            expiresAt: $expiresAt,
            subject: $user->uuid,
        )->toString();
    }

    private function ensureCompatible(Anchor $anchor): void
    {
        $compatibility = $anchor->compatibility();

        // A stale heartbeat does not prove the Anchor is down — it may just be
        // unable to reach us. Before refusing the session, try reaching it the
        // other way round; a successful probe records a heartbeat of its own,
        // so the verdict has to be recomputed from the refreshed model rather
        // than reused from above.
        if ($compatibility === AnchorCompatibility::OFFLINE) {
            $this->liveness->refresh($anchor);
            $compatibility = $anchor->compatibility();
        }

        if ($compatibility !== AnchorCompatibility::COMPATIBLE) {
            throw new ConflictHttpException(
                "Anchor {$anchor->name} is not online with a compatible protocol version.",
            );
        }
    }
}
