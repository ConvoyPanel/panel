<?php

namespace App\Services\Servers;

use App\Enums\Server\LockStatus;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Node;
use App\Models\Server;
use App\Models\Template;
use App\Repositories\Proxmox\Cluster\ProxmoxResourceRepository;
use App\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use App\Traits\HandlesProxmoxErrors;
use Illuminate\Http\Client\ConnectionException;

class ServerBuildService
{
    use HandlesProxmoxErrors;

    public function __construct(
        private ProxmoxServerRepository $serverRepository,
        private ProxmoxResourceRepository $resourceRepository,
        private ProxmoxActivityRepository $activityRepository,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function delete(Server $server): void
    {
        try {
            $this->serverRepository->setServer($server)->delete();
        } catch (RequestException $e) {
            if ($this->isNonexistentVMError($e)) {
                return;
            }

            throw $e;
        }
    }

    /**
     * @return string Job UPID
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function build(Server $server, Template $template): string
    {
        return $this->serverRepository->setServer($server)->create($template);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isVmCreated(Server $server): bool
    {
        $servers = $this->resourceRepository->setServer($server)->getResources();

        $vm = $servers->where('vmid', $server->vmid)
            ->where('lockStatus', '!=', LockStatus::CLONE)
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
        $logs = $this->activityRepository->setNode($node)->getLogsByTask(upid: $upid, limitLinesTo: 1000);

        $progressPerDisk = [];
        $currentDiskId = null;

        // Regex to identify the start of a new disk clone and capture its unique identifier.
        $diskIdRegex = '/create full clone of drive .* \((.*)\)/';
        // Regex to capture the current and total transferred data from a progress line.
        $progressRegex = '/transferred\s+([\d.]+)\s+([A-Za-z]+)\s+of\s+([\d.]+)\s+([A-Za-z]+)/';

        foreach ($logs as $log) {
            // Check if a new disk clone operation has started.
            if (preg_match($diskIdRegex, $log, $matches)) {
                $currentDiskId = $matches[1];
                // Initialize progress for this new disk if we haven't seen it before.
                if (! isset($progressPerDisk[$currentDiskId])) {
                    $progressPerDisk[$currentDiskId] = ['current' => 0, 'total' => 0];
                }
            }

            // If we are within the context of a specific disk clone, look for progress lines.
            if ($currentDiskId && preg_match($progressRegex, $log, $matches)) {
                $currentValue = (float) $matches[1];
                $currentUnit = $matches[2];
                $totalValue = (float) $matches[3];
                $totalUnit = $matches[4];

                // Update the latest progress for the current disk.
                // As we iterate, this will be overwritten until we have the final value for this disk.
                $progressPerDisk[$currentDiskId] = [
                    'current' => $this->convertToBytes($currentValue, $currentUnit),
                    'total' => $this->convertToBytes($totalValue, $totalUnit),
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
        $servers = $this->resourceRepository->setServer($server)->getResources();

        $vm = $servers->where('vmid', $server->vmid)->first();

        if ($vm) {
            return false;
        }

        return true;
    }

    /**
     * Converts a size value with a unit (e.g., B, MiB, GiB) to bytes.
     *
     * @param  float  $value  The numeric value of the size.
     * @param  string  $unit  The unit of the size.
     * @return int The calculated size in bytes.
     */
    private function convertToBytes(float $value, string $unit): int
    {
        $unit = strtoupper(trim($unit));
        switch ($unit) {
            case 'B':
                return (int) $value;
            case 'KIB':
                return (int) ($value * 1024);
            case 'MIB':
                return (int) ($value * pow(1024, 2));
            case 'GIB':
                return (int) ($value * pow(1024, 3));
            case 'TIB':
                return (int) ($value * pow(1024, 4));
            default:
                // Return 0 if the unit is not recognized to prevent errors.
                return 0;
        }
    }
}
