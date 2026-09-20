<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Server\DiskInterface;
use Convoy\Exceptions\Service\Server\Allocation\DiskResizeFailedException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxDiskRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

/**
 * Resize a disk on Proxmox and see the job through.
 *
 * `PUT /qemu/{vmid}/resize` forks a worker and hands back a UPID, so the HTTP
 * call succeeding says only that PVE accepted the request. Firing it and moving
 * on -- which is what the panel used to do -- means a failed resize is invisible:
 * the build reports success and the guest comes up at the template's own size
 * rather than the plan's.
 *
 * The retry is for an upstream problem. `PVE::Storage::Common::qemu_img_resize`
 * runs `qemu-img` under a hard-coded 10s timeout, and a resize issued straight
 * after a full clone blocks behind the writeback of the disk that was just
 * copied -- so on slower storage it is killed mid-flight and the task fails with
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
     * Attempts at the resize itself, and the pause between them. One retry: the
     * failure this exists for clears as soon as the storage finishes flushing, so
     * a resize that fails twice ten seconds apart is not a race and should be
     * reported. The worst case (30s, 10s, 30s) stays well inside SyncBuildJob's
     * five-minute retryUntil window.
     */
    private const ATTEMPTS = 2;

    private const RETRY_DELAY_SECONDS = 10;

    public function __construct(
        private ProxmoxDiskRepository     $diskRepository,
        private ProxmoxActivityRepository $activityRepository,
    )
    {
    }

    /**
     * Grow $disk to $bytes, blocking until Proxmox's task reports back.
     *
     * Safe to call repeatedly: the size goes out as an absolute value, which PVE
     * treats as a no-op once the volume is already that big (a relative `+N`
     * would stack, which is how the upstream bug report ended up with a disk
     * twice the size it asked for).
     *
     * @throws DiskResizeFailedException
     */
    public function handle(Server $server, DiskInterface $disk, int $bytes): void
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            $upid = $this->diskRepository->setServer($server)->resizeDisk($disk, $bytes);

            // No task to watch. PVE returns the UPID as a plain string; anything
            // else means this node ran the resize inline, in which case the HTTP
            // call would already have thrown had it failed.
            if (! is_string($upid) || $upid === '') {
                return;
            }

            $status = $this->awaitTask($server, $upid);

            // Still running when the window closed. Proxmox may well finish it,
            // and re-issuing now would only add a second worker queueing on the
            // same lock -- so leave it to run, as the panel did before.
            if ($status === null) {
                Log::warning('Disk resize task is still running; leaving it to finish', [
                    'server_id' => $server->id,
                    'vmid' => $server->vmid,
                    'disk' => $disk->value,
                    'upid' => $upid,
                ]);

                return;
            }

            $reason = $this->failureReason($status);

            if ($reason === null) {
                return;
            }

            if ($attempt >= self::ATTEMPTS || ! $this->isRetryable($reason)) {
                throw new DiskResizeFailedException($reason);
            }

            Log::info('Retrying a disk resize Proxmox timed out on', [
                'server_id' => $server->id,
                'vmid' => $server->vmid,
                'disk' => $disk->value,
                'reason' => $reason,
            ]);

            Sleep::for(self::RETRY_DELAY_SECONDS)->seconds();
        }
    }

    /**
     * Poll the task until it stops, or until the window closes.
     *
     * @return array<string, mixed>|null the finished task, or null if it was still running
     */
    private function awaitTask(Server $server, string $upid): ?array
    {
        for ($poll = 0; $poll < self::TASK_POLL_ATTEMPTS; $poll++) {
            try {
                $status = $this->activityRepository->setServer($server)->getStatus($upid);
            } catch (\Throwable) {
                // A task's status is not always readable the instant it starts,
                // and an old one can be rotated out of the node's log entirely.
                // Neither is a reason to fail the resize -- poll again, and let
                // the window decide.
                $status = null;
            }

            if (is_array($status) && Arr::get($status, 'status') !== 'running') {
                return $status;
            }

            Sleep::usleep(self::TASK_POLL_INTERVAL_US);
        }

        return null;
    }

    /**
     * Why the task failed, or null if it didn't. PVE reports `OK` (or
     * `WARNINGS`) on success and its raw error string on failure; a task
     * carrying no exit status at all is taken as a success.
     *
     * @param  array<string, mixed>  $status
     */
    private function failureReason(array $status): ?string
    {
        $exit = Arr::get($status, 'exitstatus');

        if (! is_string($exit) || in_array(Str::upper($exit), ['OK', 'WARNINGS'], true)) {
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
