<?php

namespace App\Jobs\Server;

use App\Enums\Activity\TaskExitStatus;
use App\Enums\Activity\TaskStatus;
use App\Models\Server;
use App\Models\Snapshot;
use App\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use App\Repositories\Proxmox\Server\ProxmoxSnapshotRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

use function App\Helpers\convertToBytes;

class MonitorSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        #[WithoutRelations]
        public Server $server,
        #[WithoutRelations]
        public Snapshot $snapshot,
        public string $upid
    ) {}

    public function handle(
        ProxmoxActivityRepository $activityRepository,
        ProxmoxSnapshotRepository $snapshotRepository
    ): void {
        $activityRepository->setServer($this->server);
        $snapshotRepository->setServer($this->server);

        $task = $activityRepository->getStatus($this->upid);
        $taskStatus = $task->status;
        $exitStatus = $task->exitStatus;

        // Check logs for size updates
        $logs = $activityRepository->getLogsByTask($this->upid, 0, 1000);
        $currentSize = 0;

        foreach ($logs as $log) {
            $size = $this->parseSize($log->text);
            if ($size !== null) {
                $currentSize = $size;
            }
        }

        if ($currentSize > 0) {
            $this->snapshot->update(['size' => $currentSize]);
        }

        // Check size limit
        // Calculate total size of *other* snapshots plus current one
        // Note: Snapshot sizes in DB (and via model accessors if not cast) might be in MB.
        // But StorageSizeCast casts access to Bytes.
        // So ->sum('size') should return total Bytes if the cast works on query (it usually doesn't on SQL sum unless retrieved).
        // Since 'size' column stores MB, ->sum('size') returns total MB.
        // We need to convert that to Bytes to compare with our Bytes size.

        $otherSnapshotsSizeMB = $this->server->snapshots()
            ->where('id', '!=', $this->snapshot->id)
            ->sum('size');

        $totalSizeBytes = ($otherSnapshotsSizeMB * 1024 * 1024) + $currentSize;
        $limitBytes = $this->server->snapshot_size_limit * 1024 * 1024;

        if ($this->server->snapshot_size_limit !== -1 && $totalSizeBytes > $limitBytes) {
             // Stop task
             $activityRepository->stop($this->upid);

             // Mark snapshot as failed/deleted
             $this->snapshot->delete();

             // Also try to delete from Proxmox just in case it finished in between
             try {
                 $snapshotRepository->delete($this->snapshot->name);
             } catch (\Throwable $e) {
                 // Ignore if not found
             }

             return;
        }

        if ($taskStatus === TaskStatus::RUNNING) {
            $this->release(3); // Retry in 3 seconds
            return;
        }

        if ($exitStatus === TaskExitStatus::OK) {
             $this->snapshot->update([
                 'completed_at' => now(),
                 'size' => $currentSize, // Final size
             ]);
        } else {
             $this->snapshot->update([
                 'errors' => $exitStatus instanceof TaskExitStatus ? $exitStatus->value : ($exitStatus ?? 'Unknown error'),
             ]);
        }
    }


    private function parseSize(string $logLine): ?int
    {
        // Matches "saved 755.88 MiB" or "428.58 MiB in 2s"
        if (preg_match('/(\d+(\.\d+)?)\s*(B|KiB|MiB|GiB|TiB)/i', $logLine, $matches)) {
            $value = floatval($matches[1]);
            $unit = $matches[3];

            return convertToBytes($value, $unit);
        }
        return null;
    }
}
