<?php

namespace App\Services\Anchor;

use App\Data\Server\ConsoleSessionData;
use App\Enums\Anchor\AnchorCompatibility;
use App\Enums\Server\ConsoleType;
use App\Models\Node;
use App\Models\Relay;
use App\Models\Server;
use App\Models\User;
use App\Services\Api\JWTService;
use App\Support\Anchor\AnchorProtocol;
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
        // The node *is* the agent now, so there is no link to be missing --
        // only an agent that was never installed on it, which is the v4 shape
        // and stays a supported one.
        $agent = $server->node;

        if (! $agent->hasAnchor()) {
            throw new ConflictHttpException('This server\'s node does not have an Anchor agent installed.');
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
                    'url' => $this->websocketUrl($agent),
                    'token' => $agentToken,
                ],
            );
            $endpoint = $agent->relay;
        } else {
            $token = $agentToken;
            $endpoint = $agent;
        }

        return new ConsoleSessionData(
            url: $this->websocketUrl($endpoint),
            token: $token,
            protocol: AnchorProtocol::VERSION,
            type: $type,
            password: $password,
        );
    }

    /** @param array<string, mixed> $console @param array<string, string>|null $relay */
    private function issue(
        Node|Relay $anchor,
        Server $server,
        User $user,
        array $console,
        CarbonImmutable $expiresAt,
        ?array $relay = null,
    ): string {
        return $this->jwt->issue(
            signingKey: $anchor->anchorSecret(),
            audience: $anchor->anchorUuid(),
            identifier: $user->uuid.$server->uuid.$anchor->anchorUuid().Str::random(),
            claims: array_filter([
                'protocol' => AnchorProtocol::VERSION,
                'console' => $console,
                'relay' => $relay,
            ], fn (mixed $value) => $value !== null),
            expiresAt: $expiresAt,
            subject: $user->uuid,
        )->toString();
    }

    /**
     * An approved Anchor always has an address -- approval is where it is
     * established -- so this never fires in practice. It exists because
     * "never in practice" is not a type, and a console that declines with a
     * sentence beats one that dies on a null deep inside token issuance.
     */
    private function websocketUrl(Node|Relay $anchor): string
    {
        return $anchor->anchorWebsocketUrl() ?? throw new ConflictHttpException(
            "Anchor {$anchor->anchorName()} has no address for the panel to reach it on.",
        );
    }

    private function ensureCompatible(Node|Relay $anchor): void
    {
        $compatibility = $anchor->anchorCompatibility();

        // A stale heartbeat does not prove the Anchor is down — it may just be
        // unable to reach us. Before refusing the session, try reaching it the
        // other way round; a successful probe records a heartbeat of its own,
        // so the verdict has to be recomputed from the refreshed model rather
        // than reused from above.
        if ($compatibility === AnchorCompatibility::OFFLINE) {
            $this->liveness->refresh($anchor);
            $compatibility = $anchor->anchorCompatibility();
        }

        if ($compatibility !== AnchorCompatibility::COMPATIBLE) {
            throw new ConflictHttpException(
                "Anchor {$anchor->anchorName()} is not online with a compatible protocol version.",
            );
        }
    }
}
