<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Activity\TaskData;
use App\Data\Server\Proxmox\Config\DiskData;
use App\Enums\Activity\TaskExitStatus;
use App\Enums\Activity\TaskStatus;
use App\Exceptions\Proxmox\RequestException;
use App\Exceptions\Service\Server\Allocation\DiskResizeFailedException;
use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxActivityClient;
use App\Services\Proxmox\Server\ProxmoxDiskClient;
use App\Services\Servers\Power\ServerPowerLockService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

/**
 * Resize a disk on Proxmox and see the job through.
 *
 * `PUT /qemu/{vmid}/resize` forks a worker and hands back a UPID, so the HTTP
 * call succeeding says only that PVE accepted the request. Firing it and moving
 * on -- which is what the panel used to do -- has two consequences, both of
 * which operators hit during a build:
 *
 *  - A failed resize is invisible. The build reports success and the guest comes
 *    up at the image's own size rather than the plan's.
 *  - The resize worker holds `/var/lock/qemu-server/lock-<vmid>.conf` for as long
 *    as `qemu-img` runs, and the very next config write in {@see VmSyncService}
 *    walks into it. PVE's config lock gives up after 10s, so that write dies with
 *    `can't lock file ... - got timeout` even though nothing was wrong with it.
 *
 * Both go away by waiting for the task before returning.
 *
 * The retry is for a separate, upstream problem. `PVE::Storage::Common::qemu_img_resize`
 * runs `qemu-img` under a hard-coded 10s timeout, and a resize issued straight
 * after an import blocks behind the writeback of the disk that was just copied --
 * so on slower storage it is killed mid-flight and the task fails with
 * `qemu-img resize ... failed: got timeout`. That is Proxmox bug #7598, fixed
 * upstream by raising the timeout to an hour in worker context, but the fix is
 * not in any released PVE 9.x -- so the panel has to survive it. Waiting a few
 * seconds is enough: by then the flush has settled and the resize is instant.
 *
 * One upstream wart is worth knowing about and is deliberately not worked around
 * here. When `qemu-img` is killed, the truncate has usually already landed on the
 * storage -- PVE just never got to record the new `size=` in the VM config. A
 * retry therefore sees a volume that is already the right size, returns early
 * without rewriting the config, and reports success. The guest gets the disk it
 * is owed (which is what matters) while PVE's config keeps quoting the old size.
 * Repairing that annotation would mean writing to the config behind PVE's back.
 */
class DiskResizeService
{
    /**
     * How long to watch the resize task, and how long to wait between reads.
     *
     * 30s of polling is far longer than a resize needs -- with preallocation off
     * it is a truncate -- and comfortably longer than the 10s at which PVE gives
     * up on a slow one, so a failure is observed rather than guessed at.
     */
    private const TASK_POLL_ATTEMPTS = 60;

    private const TASK_POLL_INTERVAL_US = 500_000;

    /**
     * Attempts at the resize itself, and the pause between them.
     *
     * One retry, because the failure this exists for clears as soon as the
     * storage finishes flushing; a resize that fails twice ten seconds apart is
     * not a race and should be reported. The whole worst case -- 30s, 10s, 30s --
     * stays under {@see ExpiringWithoutOverlapping::EXPIRES_AFTER},
     * so the server's overlap lock cannot expire underneath a build that is
     * still working.
     */
    private const ATTEMPTS = 2;

    private const RETRY_DELAY_SECONDS = 10;

    public function __construct(
        private ProxmoxDiskClient $diskClient,
        private ProxmoxActivityClient $activityClient,
    ) {}

    /**
     * Grow $disk to $bytes, blocking until Proxmox's task reports back.
     *
     * Safe to call repeatedly: the size goes out as an absolute value, which PVE
     * treats as a no-op once the volume is already that big (a relative `+N`
     * would stack, which is how the bug report above ended up with a disk twice
     * the size it asked for).
     *
     * @throws DiskResizeFailedException when the node reports the task failed
     * @throws RequestException
     * @throws ConnectionException
     */
    public function resize(Server $server, DiskData $disk, int $bytes): void
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            $upid = $this->diskClient->setServer($server)->setDiskSize($disk, $bytes);

            // No task to watch. PVE returns the UPID as a plain string; anything
            // else means this node ran the resize inline, in which case the HTTP
            // call would already have thrown had it failed.
            if (! is_string($upid) || $upid === '') {
                return;
            }

            $task = $this->awaitTask($server, $upid);

            // Still running when the window closed. Proxmox may well finish it,
            // and re-issuing now would only add a second worker queueing on the
            // same lock -- so leave it to run, as the panel did before.
            if ($task === null) {
                Log::warning('Disk resize task is still running; leaving it to finish', [
                    'server_id' => $server->id,
                    'vmid' => $server->vmid,
                    'disk' => $disk->interface->value,
                    'upid' => $upid,
                ]);

                return;
            }

            $reason = $this->failureReason($task);

            if ($reason === null) {
                return;
            }

            if ($attempt >= self::ATTEMPTS || ! $this->isRetryable($reason)) {
                throw new DiskResizeFailedException($reason);
            }

            Log::info('Retrying a disk resize Proxmox timed out on', [
                'server_id' => $server->id,
                'vmid' => $server->vmid,
                'disk' => $disk->interface->value,
                'reason' => $reason,
            ]);

            Sleep::for(self::RETRY_DELAY_SECONDS)->seconds();
        }
    }

    /**
     * Poll the task until it stops, or until the window closes.
     *
     * @return ?TaskData the finished task, or null if it was still running
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    private function awaitTask(Server $server, string $upid): ?TaskData
    {
        for ($poll = 0; $poll < self::TASK_POLL_ATTEMPTS; $poll++) {
            try {
                $task = $this->activityClient->setNode($server->node)->getStatus($upid);
            } catch (\Throwable) {
                // A task's status is not always readable the instant it starts,
                // and an old one can be rotated out of the node's log entirely.
                // Neither is a reason to fail the resize -- poll again, and let
                // the window decide, the same way the import step does.
                $task = null;
            }

            if ($task !== null && $task->status !== TaskStatus::RUNNING) {
                return $task;
            }

            Sleep::usleep(self::TASK_POLL_INTERVAL_US);
        }

        return null;
    }

    /**
     * Why the task failed, or null if it didn't.
     *
     * PVE reports `OK` (or `WARNINGS`) on success and its raw error string on
     * failure. A task carrying no exit status at all is taken as a success, the
     * same reading {@see ServerPowerLockService} gives it.
     */
    private function failureReason(TaskData $task): ?string
    {
        $exit = $task->exitStatus;

        if ($exit === null || $exit instanceof TaskExitStatus) {
            return null;
        }

        return $exit;
    }

    /**
     * Whether a second attempt is worth making.
     *
     * Narrow on purpose: `got timeout` is how PVE reports both halves of the race
     * this class exists for -- `qemu-img` killed mid-resize, and a config lock it
     * could not take -- and both clear on their own. Anything else (no space on
     * the storage, a volume that isn't there) would fail again the same way.
     */
    private function isRetryable(string $reason): bool
    {
        return Str::contains($reason, 'got timeout', ignoreCase: true);
    }
}
