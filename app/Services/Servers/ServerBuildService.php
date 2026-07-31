<?php

namespace App\Services\Servers;

use App\Enums\Server\ProxmoxLock;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Node;
use App\Models\Server;
use App\Models\Template;
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
    public function build(Server $server, Template $template): string
    {
        return $this->serverClient->setServer($server)->create($template);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isVmCreated(Server $server): bool
    {
        $servers = $this->resourceClient->setServer($server)->getResources();

        $vm = $servers->where('vmid', $server->vmid)
            ->where('lockStatus', '!=', ProxmoxLock::CLONE)
            ->first();

        if ($vm) {
            return true;
        }

        return false;
    }

    /**
     * Calculates the total and current progress of a clone operation from task logs.
     * It handles tasks that involve cloning multiple disks by aggregating their progress.
     *
     * @param  string  $upid  The unique process ID for the task.
     * @return array [int, int] An array containing the total size and current progress in bytes.
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function getCloneProgress(Node $node, string $upid): array
    {
        // Get logs in chronological order to correctly track the context of each clone operation.
        $logs = $this->activityClient->setNode($node)->getLogsByTask(upid: $upid, limitLinesTo: 1000);

        $progressPerDisk = [];
        $currentDiskId = null;

        // Regex to identify the start of a new disk clone and capture its unique identifier.
        $diskIdRegex = '/create full clone of drive .* \((.*)\)/';
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
