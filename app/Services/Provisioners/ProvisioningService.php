<?php

namespace App\Services\Provisioners;

use App\Enums\Provisioner\ProvisioningStatus;
use App\Exceptions\Proxmox\RequestException;
use App\Exceptions\Service\Provisioner\ProvisioningException;
use App\Jobs\Provisioner\CleanUpProvisioningRunJob;
use App\Models\Deployment;
use App\Models\ProvisioningRun;
use App\Models\Server;
use App\Services\Anchor\AnchorSnippetClient;
use App\Services\Proxmox\Server\ProxmoxConfigClient;
use App\Support\Anchor\AnchorSnippetProtocol;
use App\Traits\HandlesProxmoxErrors;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;

/**
 * Puts a server's provisioner scripts on its node, and takes them off again.
 *
 * The order matters at both ends. The snippet is written before `cicustom`
 * points at it, and `cicustom` is removed before the snippet is deleted: a
 * guest whose config names a snippet that is not there cannot start.
 */
class ProvisioningService
{
    use HandlesProxmoxErrors;

    public function __construct(
        private AnchorSnippetClient $snippets,
        private ProxmoxConfigClient $configClient,
        private ProvisionerSnippet $snippet,
        private ProvisionerEnvironment $environment,
    ) {}

    /**
     * Write the snippet for this deployment and point the guest at it.
     *
     * Safe to run again: a retry reuses the deployment's run, writes the same
     * file, and mints a new token, so the token in the snippet on the node is
     * always the one the panel will accept.
     *
     * @param  array<string, string|null>  $fieldValues  by field key
     *
     * @throws ProvisioningException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function prepare(Deployment $deployment, array $fieldValues): ?ProvisioningRun
    {
        $server = $deployment->server;
        $node = $server->node;
        $image = $deployment->imageDefinition;

        $this->cancelOpenRuns($server, except: $deployment);

        if ($image === null || $image->provisioners->isEmpty()) {
            return null;
        }

        $provisioners = $image->provisioners;

        if (! $node->canWriteSnippets()) {
            throw new ProvisioningException("Anchor on {$node->name} cannot write provisioner snippets. Update it, then rebuild the server.");
        }

        $storage = $node->snippetStorage()
            ?? throw new ProvisioningException("{$node->name} has no storage with the Snippets content type.");

        $token = Str::random(64);

        $run = ProvisioningRun::firstOrNew(['deployment_id' => $deployment->id]);
        $run->fill([
            'server_id' => $server->id,
            'node_id' => $node->id,
            'storage' => $storage->name,
            'token_hash' => ProvisioningRun::hashToken($token),
            'status' => ProvisioningStatus::PENDING,
            'provisioners' => $provisioners
                ->map(fn ($provisioner) => ['uuid' => $provisioner->uuid, 'name' => $provisioner->name])
                ->values()
                ->all(),
            'results' => null,
            'deadline_at' => null,
            'reported_at' => null,
            'cleaned_up_at' => null,
        ])->save();

        $contents = $this->snippet->build(
            $provisioners,
            $this->environment->for($server, $image, $image->provisionerFields(), $fieldValues),
            route('provisioning.report'),
            $token,
        );

        if (strlen($contents) > AnchorSnippetProtocol::MAX_BYTES) {
            throw new ProvisioningException('The provisioner scripts on this image are too large to send to the node together.');
        }

        $this->snippets->write($node, $run->storage, $run->snippet, $contents);

        $this->configClient->setServer($server)->update(['cicustom' => 'vendor='.$run->volume()]);

        if ($deployment->start_on_completion) {
            ProvisioningRun::startClockFor($server);
        }

        return $run->refresh();
    }

    /**
     * Record what the guest reported, then clean up.
     *
     * @param  array<int, array{provisioner: string, exit_code: int}>  $results
     */
    public function report(ProvisioningRun $run, array $results): void
    {
        $byProvisioner = collect($results)->keyBy('provisioner');

        $succeeded = collect($run->provisioners)->every(
            fn (array $provisioner) => ($byProvisioner->get($provisioner['uuid'])['exit_code'] ?? null) === 0,
        );

        $run->update([
            'status' => $succeeded ? ProvisioningStatus::SUCCEEDED : ProvisioningStatus::FAILED,
            'results' => collect($results)
                ->map(fn (array $result) => ['provisioner' => $result['provisioner'], 'exit_code' => (int) $result['exit_code']])
                ->values()
                ->all(),
            'reported_at' => now(),
        ]);

        if ($run->cleaned_up_at === null) {
            CleanUpProvisioningRunJob::dispatch($run->id)->afterCommit();
        }
    }

    /**
     * Stop waiting on a server's earlier runs: a rebuild replaces the guest
     * they were written for.
     */
    public function cancelOpenRuns(Server $server, ?Deployment $except = null): void
    {
        $runs = ProvisioningRun::query()
            ->where('server_id', $server->id)
            ->uncleaned()
            ->when($except, fn ($query) => $query->where(
                fn ($query) => $query->whereNull('deployment_id')->orWhere('deployment_id', '!=', $except->id),
            ))
            ->get();

        foreach ($runs as $run) {
            if ($run->status === ProvisioningStatus::PENDING) {
                $run->update(['status' => ProvisioningStatus::CANCELLED]);
            }

            CleanUpProvisioningRunJob::dispatch($run->id)->afterCommit();
        }
    }

    /**
     * Take the snippet off the node, synchronously, before something that
     * would carry `cicustom` somewhere the snippet is not -- a migration.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function releaseNow(Server $server): void
    {
        $runs = ProvisioningRun::query()
            ->where('server_id', $server->id)
            ->uncleaned()
            ->get();

        foreach ($runs as $run) {
            if ($run->status === ProvisioningStatus::PENDING) {
                $run->update(['status' => ProvisioningStatus::CANCELLED]);
            }

            $this->cleanUp($run);
        }
    }

    /**
     * Remove `cicustom` from the guest, then delete the snippet.
     *
     * `cicustom` is only removed while it still names this run's snippet, so
     * cleaning up a run a rebuild replaced never touches the new guest's.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function cleanUp(ProvisioningRun $run): void
    {
        if ($run->cleaned_up_at !== null) {
            return;
        }

        if ($server = $run->server) {
            try {
                $config = $this->configClient->setServer($server)->getRawConfig();

                if (str_contains((string) ($config['cicustom'] ?? ''), $run->snippet)) {
                    $this->configClient->update(['delete' => 'cicustom']);
                }
            } catch (RequestException $e) {
                if (! $this->isNonexistentVMError($e)) {
                    throw $e;
                }
            }
        }

        $this->snippets->delete($run->node, $run->storage, $run->snippet);

        $run->update(['cleaned_up_at' => now()]);
    }

    /**
     * Drop a `cicustom` that names a snippet the node no longer has.
     *
     * A backup taken while the server was still waiting on its scripts
     * restores that line, and by then the snippet is long gone. A guest whose
     * config names a missing snippet cannot start.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function dropStaleCicustom(Server $server): void
    {
        $cicustom = (string) ($this->configClient->setServer($server)->getRawConfig()['cicustom'] ?? '');

        if (! preg_match('/convoy-provisioner-[0-9a-f-]+\.yaml/', $cicustom, $match)) {
            return;
        }

        $live = ProvisioningRun::query()
            ->where('server_id', $server->id)
            ->where('snippet', $match[0])
            ->uncleaned()
            ->exists();

        if (! $live) {
            $this->configClient->update(['delete' => 'cicustom']);
        }
    }

    /**
     * Give up on runs past their deadline, cancel runs whose server is gone,
     * and retry cleanups that failed.
     */
    public function sweep(): void
    {
        $expired = ProvisioningRun::query()
            ->where('status', ProvisioningStatus::PENDING)
            ->where('deadline_at', '<', now())
            ->get();

        foreach ($expired as $run) {
            $run->update(['status' => ProvisioningStatus::TIMED_OUT]);
            CleanUpProvisioningRunJob::dispatch($run->id)->afterCommit();
        }

        $orphaned = ProvisioningRun::query()
            ->whereNull('server_id')
            ->uncleaned()
            ->get();

        foreach ($orphaned as $run) {
            if ($run->status === ProvisioningStatus::PENDING) {
                $run->update(['status' => ProvisioningStatus::CANCELLED]);
            }

            CleanUpProvisioningRunJob::dispatch($run->id)->afterCommit();
        }

        // A cleanup whose job gave up (the node was down for a while) is
        // picked up again here. Ten minutes leaves a fresh one to its own job.
        ProvisioningRun::query()
            ->whereNotNull('server_id')
            ->where('status', '!=', ProvisioningStatus::PENDING)
            ->uncleaned()
            ->where('updated_at', '<', now()->subMinutes(10))
            ->pluck('id')
            ->each(fn (int $id) => CleanUpProvisioningRunJob::dispatch($id)->afterCommit());
    }
}
