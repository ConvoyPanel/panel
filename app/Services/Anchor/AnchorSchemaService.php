<?php

namespace App\Services\Anchor;

use App\Models\Node;
use App\Services\Api\JWTService;
use App\Support\Anchor\AnchorProtocol;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Proxmox's own definition of what `qm create` accepts, read off a node.
 *
 * PVE describes its entire API in JSON Schema and ships the result on every
 * node, so the panel never has to hard-code what a valid `scsihw` is, or keep a
 * list of enums in step with Proxmox releases by hand. Reading it per node also
 * means the answer is right for *that* node's PVE version, which is the only
 * version whose opinion matters when the create call finally lands.
 *
 * A node that cannot answer -- no docs package, an older Anchor, simply offline
 * -- falls back to a small bundled copy. That is a real downgrade: the bundled
 * copy knows a fraction of the parameters and none of this node's specifics. It
 * exists so an obviously wrong profile is still refused at submit rather than
 * accepted and discovered at first power-on.
 */
class AnchorSchemaService
{
    private const TIMEOUT_SECONDS = 10;

    /**
     * A schema only changes when the node is upgraded, and being a day stale is
     * harmless: the create call validates server-side anyway. Being slow on
     * every keystroke in the hardware form would not be.
     */
    private const CACHE_MINUTES = 1440;

    public function __construct(private JWTService $jwt) {}

    /**
     * @return array<string, array<string, mixed>> keyed by parameter name
     */
    public function forNode(?Node $node): array
    {
        if (is_null($node)) {
            return $this->bundled();
        }

        return Cache::remember(
            $this->cacheKey($node),
            now()->addMinutes(self::CACHE_MINUTES),
            fn () => $this->fetch($node) ?? $this->bundled(),
        );
    }

    public function forget(Node $node): void
    {
        Cache::forget($this->cacheKey($node));
    }

    /**
     * The panel's own copy, used when no node can be asked.
     */
    public function bundled(): array
    {
        $path = resource_path('pve/qemu-create-schema.json');

        $decoded = json_decode((string) @file_get_contents($path), true);

        return is_array($decoded['parameters'] ?? null) ? $decoded['parameters'] : [];
    }

    /**
     * @return array<string, array<string, mixed>>|null null when this node cannot answer
     */
    private function fetch(Node $node): ?array
    {
        $base = $node->anchorPublicUrl();
        $secret = $node->anchorSecret();
        $audience = $node->anchorUuid();

        if (blank($base) || blank($secret) || blank($audience)) {
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withToken($this->token($node))
                ->get(rtrim($base, '/').'/api/v1/pve/schema');
        } catch (\Throwable $exception) {
            Log::debug('Could not read the Proxmox API schema from Anchor.', [
                'node' => $node->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $parameters = $response->json('parameters');

        // An empty map is not a usable answer, and caching one would hide a
        // broken docs package behind a day of silent permissiveness.
        return is_array($parameters) && $parameters !== [] ? $parameters : null;
    }

    /**
     * The work order travels inside the token, as it does for every other Anchor
     * call: a captured token can read a schema and nothing else.
     */
    private function token(Node $node): string
    {
        return $this->jwt->issue(
            signingKey: (string) $node->anchorSecret(),
            audience: (string) $node->anchorUuid(),
            identifier: $node->anchorUuid().Str::random(),
            claims: [
                'protocol' => AnchorProtocol::VERSION,
                'schema' => ['action' => 'qemu_create'],
            ],
            expiresAt: CarbonImmutable::now()->addMinutes(2),
            subject: (string) $node->anchorUuid(),
        )->toString();
    }

    private function cacheKey(Node $node): string
    {
        return "pve-schema:qemu-create:{$node->id}";
    }
}
