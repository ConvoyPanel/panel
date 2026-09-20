<?php

namespace App\Services\Anchor;

use App\Data\Anchor\AnchorJobData;
use App\Exceptions\Service\Anchor\AnchorRequestException;
use App\Models\Node;
use App\Services\Api\JWTService;
use App\Support\Anchor\AnchorMigrationProtocol;
use App\Support\Anchor\AnchorProtocol;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * The panel's half of the migration protocol, spoken to one node at a time.
 *
 * Every call carries its work order *inside* the token, the way
 * {@see AnchorSchemaService} and {@see AnchorSessionService} already do: a
 * token minted to poll job A cannot cancel job B, and one minted to let a
 * destination download artifact X cannot delete it or read anything else in
 * the node's dump directory. That is the whole reason there is no bearer token
 * for "migrations" as a category.
 *
 * Nothing here transfers bytes. The archive goes straight from the source node
 * to the destination node over the tailnet they share; the panel only hands
 * the destination a URL and a hash.
 */
class AnchorMigrationClient
{
    public function __construct(private JWTService $jwt) {}

    /**
     * Ask the source to dump a guest. Returns the job to poll.
     *
     * `stop` is the only mode a migration may use and the agent defaults to
     * it, but the panel sends it anyway: the invariant that the guest is down
     * for the whole transfer should be legible on the wire, not inferred from
     * a default.
     */
    public function export(Node $source, int $vmid): AnchorJobData
    {
        return $this->job($this->send(
            $source,
            'post',
            AnchorMigrationProtocol::EXPORTS,
            ['action' => 'export', 'vmid' => $vmid, 'mode' => 'stop', 'compress' => 'zstd'],
        ));
    }

    /**
     * Hand the destination a URL and a hash and let it pull.
     *
     * `overwrite` is deliberately absent: a destination VMID is chosen from
     * the destination's own free range, so restoring over an existing guest is
     * never what a migration wants. If the VMID is taken the agent refuses,
     * the step fails, and the rollback puts the source back.
     */
    public function install(
        Node $destination,
        string $name,
        string $url,
        string $sha256,
        ?int $size,
        int $vmid,
        string $storage,
    ): AnchorJobData {
        return $this->job($this->send(
            $destination,
            'post',
            AnchorMigrationProtocol::INSTALLS,
            [
                'action' => 'install',
                'name' => $name,
                'url' => $url,
                'sha256' => $sha256,
                'size' => $size,
                'vmid' => $vmid,
                'storage' => $storage,
            ],
        ));
    }

    /** Where an export job has got to. */
    public function exportStatus(Node $node, string $jobId): AnchorJobData
    {
        return $this->job($this->send(
            $node,
            'get',
            AnchorMigrationProtocol::url('', AnchorMigrationProtocol::EXPORT, ['job' => $jobId]),
            ['action' => 'status', 'job' => $jobId],
        ));
    }

    /** Where an install job has got to. */
    public function installStatus(Node $node, string $jobId): AnchorJobData
    {
        return $this->job($this->send(
            $node,
            'get',
            AnchorMigrationProtocol::url('', AnchorMigrationProtocol::INSTALL, ['job' => $jobId]),
            ['action' => 'status', 'job' => $jobId],
        ));
    }

    public function cancelExport(Node $node, string $jobId): void
    {
        $this->send(
            $node,
            'delete',
            AnchorMigrationProtocol::url('', AnchorMigrationProtocol::EXPORT, ['job' => $jobId]),
            ['action' => 'cancel', 'job' => $jobId],
        );
    }

    public function cancelInstall(Node $node, string $jobId): void
    {
        $this->send(
            $node,
            'delete',
            AnchorMigrationProtocol::url('', AnchorMigrationProtocol::INSTALL, ['job' => $jobId]),
            ['action' => 'cancel', 'job' => $jobId],
        );
    }

    /** Delete one finished archive on the node that produced it. */
    public function discard(Node $source, string $artifact): void
    {
        $this->send(
            $source,
            'delete',
            AnchorMigrationProtocol::url('', AnchorMigrationProtocol::ARTIFACT, ['artifact' => $artifact]),
            ['action' => 'discard', 'artifact' => $artifact],
        );
    }

    /**
     * The URL the destination downloads from, with its own token in it.
     *
     * The token goes in the query string because it has to: the install
     * pipeline fetches `spec.url` with no header of its own, and the contract
     * reuses that pipeline unchanged. It is a capability for one artifact on
     * one node, mints nothing else, and expires, which is the same trade the
     * panel already makes for temporary image URLs.
     */
    public function artifactUrl(Node $source, string $artifact): string
    {
        $base = $source->anchorPublicUrl();

        if (blank($base)) {
            throw new AnchorRequestException(sprintf(
                'Anchor on %s has no address the destination node could download from.',
                $source->name,
            ));
        }

        $token = $this->token($source, ['action' => 'fetch', 'artifact' => $artifact], CarbonImmutable::now()
            ->addMinutes(AnchorMigrationProtocol::ARTIFACT_TOKEN_MINUTES));

        return AnchorMigrationProtocol::url($base, AnchorMigrationProtocol::FETCH, ['artifact' => $artifact])
            .'?token='.urlencode($token);
    }

    /**
     * @param  array<string, mixed>  $action  the work order, embedded in the token
     */
    private function send(Node $node, string $method, string $path, array $action): Response
    {
        $base = $node->anchorPublicUrl();

        if (blank($base) || blank($node->anchorSecret()) || blank($node->anchorUuid())) {
            throw new AnchorRequestException(sprintf(
                'Anchor on %s is not reachable: the panel has no address or key for it.',
                $node->name,
            ));
        }

        $url = AnchorMigrationProtocol::url((string) $base, $path);

        try {
            /** @var Response $response */
            $response = $this->request($node, $action)->{$method}($url);
        } catch (AnchorRequestException $exception) {
            throw $exception;
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
                // The agent answers with `{"error": "..."}`; a proxy in front
                // of it may answer with anything at all, so fall back to the
                // status rather than printing a page of HTML at the operator.
                $response->json('error') ?? 'HTTP '.$response->status(),
            ));
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function request(Node $node, array $action): PendingRequest
    {
        return Http::timeout(AnchorMigrationProtocol::TIMEOUT_SECONDS)
            ->acceptJson()
            ->withToken($this->token($node, $action, CarbonImmutable::now()->addMinutes(2)));
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function token(Node $node, array $action, CarbonImmutable $expiresAt): string
    {
        return $this->jwt->issue(
            signingKey: (string) $node->anchorSecret(),
            audience: (string) $node->anchorUuid(),
            identifier: $node->anchorUuid().Str::random(),
            claims: [
                'protocol' => AnchorProtocol::VERSION,
                // `template` rather than `migration`: the agent decodes one
                // claim set for all of this, and adding a second key for the
                // same actions would mean two token shapes for one registry.
                'template' => $action,
            ],
            expiresAt: $expiresAt,
            subject: (string) $node->anchorUuid(),
        )->toString();
    }

    private function job(Response $response): AnchorJobData
    {
        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['id'])) {
            throw new AnchorRequestException('Anchor accepted the request but did not say which job it started.');
        }

        return AnchorJobData::fromRaw($payload);
    }
}
