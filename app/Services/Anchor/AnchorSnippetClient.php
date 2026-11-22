<?php

namespace App\Services\Anchor;

use App\Exceptions\Service\Anchor\AnchorRequestException;
use App\Models\Node;
use App\Services\Api\JWTService;
use App\Support\Anchor\AnchorMigrationProtocol;
use App\Support\Anchor\AnchorProtocol;
use App\Support\Anchor\AnchorSnippetProtocol;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes and deletes provisioner snippets on a node.
 *
 * Like {@see AnchorMigrationClient}, each token carries its own work order: a
 * write token names the storage, the file and the hash of the bytes it may
 * write, so a captured one cannot write anything else.
 */
class AnchorSnippetClient
{
    public function __construct(private JWTService $jwt) {}

    public function write(Node $node, string $storage, string $name, string $contents): void
    {
        $this->send(
            $node,
            ['action' => 'write', 'storage' => $storage, 'name' => $name, 'sha256' => hash('sha256', $contents)],
            fn (PendingRequest $request, string $url) => $request
                ->withBody($contents, 'application/yaml')
                ->put($url),
        );
    }

    /** Deleting a snippet that is already gone succeeds. */
    public function delete(Node $node, string $storage, string $name): void
    {
        $this->send(
            $node,
            ['action' => 'delete', 'storage' => $storage, 'name' => $name],
            fn (PendingRequest $request, string $url) => $request->delete($url),
        );
    }

    /**
     * @param  array<string, string>  $action
     * @param  callable(PendingRequest, string): Response  $call
     */
    private function send(Node $node, array $action, callable $call): Response
    {
        $base = $node->anchorPublicUrl();

        if (blank($base) || blank($node->anchorSecret()) || blank($node->anchorUuid())) {
            throw new AnchorRequestException(sprintf(
                'Anchor on %s is not reachable: the panel has no address or key for it.',
                $node->name,
            ));
        }

        $url = AnchorMigrationProtocol::url((string) $base, AnchorSnippetProtocol::SNIPPET, ['name' => $action['name']]);

        $request = Http::timeout(AnchorSnippetProtocol::TIMEOUT_SECONDS)
            ->acceptJson()
            ->withToken($this->token($node, $action));

        try {
            $response = $call($request, $url);
        } catch (Throwable $exception) {
            throw new AnchorRequestException(sprintf(
                'Could not reach Anchor on %s: %s',
                $node->name,
                $exception->getMessage(),
            ), previous: $exception);
        }

        if (! $response->successful()) {
            throw new AnchorRequestException(sprintf(
                'Anchor on %s refused the request: %s',
                $node->name,
                $response->json('error') ?? 'HTTP '.$response->status(),
            ));
        }

        return $response;
    }

    /**
     * @param  array<string, string>  $action
     */
    private function token(Node $node, array $action): string
    {
        return $this->jwt->issue(
            signingKey: (string) $node->anchorSecret(),
            audience: (string) $node->anchorUuid(),
            identifier: $node->anchorUuid().Str::random(),
            claims: [
                'protocol' => AnchorProtocol::VERSION,
                'snippet' => $action,
            ],
            expiresAt: CarbonImmutable::now()->addMinutes(2),
            subject: (string) $node->anchorUuid(),
        )->toString();
    }
}
