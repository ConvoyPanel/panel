<?php

namespace App\Services\Servers;

use App\Enums\Server\ProxmoxLock;
use App\Exceptions\Proxmox\RequestException;
use App\Models\ImageVersion;
use App\Models\Node;
use App\Models\Server;
use App\Services\Proxmox\Cluster\ProxmoxResourceClient;
use App\Services\Proxmox\Server\ProxmoxActivityClient;
use App\Services\Proxmox\Server\ProxmoxServerClient;
use App\Support\ByteUnit;
use Illuminate\Http\Client\ConnectionException;

class ServerBuildService
{
    public function __construct(
        private ProxmoxServerClient $serverClient,
        private ProxmoxResourceClient $resourceClient,
        private ProxmoxActivityClient $activityClient,
    ) {}

    /**
     * @return string Task UPID
     *
     * A VM that is already gone surfaces as a RequestException the caller
     * recognises via HandlesProxmoxErrors and treats as an already-complete
     * delete, so this no longer swallows that case itself.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function delete(Server $server): string
    {
        return $this->serverClient->setServer($server)->delete();
    }

    /**
     * @return string Job UPID
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function build(Server $server, ImageVersion $version, array $volids): string
    {
        return $this->serverClient->setServer($server)->create($version, $volids);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isVmCreated(Server $server): bool
    {
        $servers = $this->resourceClient->setServer($server)->getResources();

        // The import holds a `create` lock rather than a `clone` one. Reading
        // the wrong lock would report the VM ready while Proxmox was still
        // writing its disk, and every step after this assumes a finished guest.
        $vm = $servers->where('vmid', $server->vmid)
            ->where('lockStatus', '!=', ProxmoxLock::CREATE)
            ->first();

        if ($vm) {
            return true;
        }

        return false;
    }

    /**
     * Byte progress of an import, read from the task log.
     *
     * Proxmox reports a disk import the way it reported a clone -- one
     * `transferred X of Y` line per drive, rewritten as it goes -- so the
     * aggregation is unchanged; only the line that opens a new drive differs.
     * Both openers are matched because a node mid-upgrade can emit either, and
     * an unrecognised log costs a progress bar rather than a build: completion
     * is decided by the guest's lock state, never by this.
     *
     * @param  string  $upid  The unique process ID for the task.
     * @return array [int, int] Current and total bytes.
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getImportProgress(Node $node, string $upid): array
    {
        // Get logs in chronological order to correctly track the context of each clone operation.
        $logs = $this->activityClient->setNode($node)->getLogsByTask(upid: $upid, limitLinesTo: 1000);

        $progressPerDisk = [];
        $currentDiskId = null;

        // Regex to identify the start of a new disk clone and capture its unique identifier.
        $diskIdRegex = '/(?:create full clone of drive|importing disk .* to) .*\((.*)\)/';
        // Regex to capture the current and total transferred data from a progress line.
        $progressRegex = '/transferred\s+([\d.]+)\s+([A-Za-z]+)\s+of\s+([\d.]+)\s+([A-Za-z]+)/';

        foreach ($logs as $log) {
            $line = $log->text;
            // Check if a new disk clone operation has started.
            if (preg_match($diskIdRegex, $line, $matches)) {
                $currentDiskId = $matches[1];
                // Initialize progress for this new disk if we haven't seen it before.
                if (! isset($progressPerDisk[$currentDiskId])) {
                    $progressPerDisk[$currentDiskId] = ['current' => 0, 'total' => 0];
                }
            }

            // If we are within the context of a specific disk clone, look for progress lines.
            if ($currentDiskId && preg_match($progressRegex, $line, $matches)) {
                $currentValue = (float) $matches[1];
                $currentUnit = $matches[2];
                $totalValue = (float) $matches[3];
                $totalUnit = $matches[4];

                // Update the latest progress for the current disk.
                // As we iterate, this will be overwritten until we have the final value for this disk.
                $progressPerDisk[$currentDiskId] = [
                    'current' => ByteUnit::fromIec($currentUnit)?->toBytes($currentValue) ?? 0,
                    'total' => ByteUnit::fromIec($totalUnit)?->toBytes($totalValue) ?? 0,
                ];
            }
        }

        $total = 0;
        $current = 0;

        // Sum up the final progress from all disk operations found in the logs.
        foreach ($progressPerDisk as $progress) {
            $total += $progress['total'];
            $current += $progress['current'];
        }

        return [$current, $total];
    }

    /**
     * Byte progress of an image download onto a node.
     *
     * Separate from the import because it is a storage task with a different
     * log, and deliberately tolerant: several shapes of progress line are
     * accepted and an unrecognised one simply yields no reading. The fetch step
     * finishes when the file is actually on the node, so a log this cannot
     * parse costs the bar its movement and nothing else.
     *
     * @return array [int, int] Current and total bytes; [0, 0] when unreadable.
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getDownloadProgress(Node $node, string $upid): array
    {
        $logs = $this->activityClient->setNode($node)->getLogsByTask(upid: $upid, limitLinesTo: 1000);

        $current = 0;
        $total = 0;

        foreach ($logs as $log) {
            if (preg_match('/([\d.]+)\s*([KMGT]i?B)\s+of\s+([\d.]+)\s*([KMGT]i?B)/i', $log->text, $matches)) {
                $current = ByteUnit::fromIec($matches[2])?->toBytes((float) $matches[1]) ?? $current;
                $total = ByteUnit::fromIec($matches[4])?->toBytes((float) $matches[3]) ?? $total;
            }
        }

        return [$current, $total];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isVmDeleted(Server $server): bool
    {
        $servers = $this->resourceClient->setServer($server)->getResources();

        $vm = $servers->where('vmid', $server->vmid)->first();

        if ($vm) {
            return false;
        }

        return true;
    }
}
