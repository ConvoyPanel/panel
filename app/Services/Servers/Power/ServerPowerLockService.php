<?php

namespace App\Services\Servers\Power;

use App\Data\Server\Power\PendingPowerActionData;
use App\Data\Server\Power\PowerActionResultData;
use App\Data\Server\Proxmox\Activity\TaskData;
use App\Enums\Activity\TaskExitStatus;
use App\Enums\Activity\TaskStatus;
use App\Enums\Server\PowerCommand;
use App\Exceptions\Http\Server\PowerActionInProgressException;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxActivityClient;
use Illuminate\Support\Facades\Cache;

/**
 * Guards interactive power commands with a short-lived per-server lock so a user
 * cannot spam start/stop/reboot and enqueue conflicting Proxmox tasks.
 *
 * The lock doubles as the "pending action" record: a single atomic cache entry
 * (`Cache::add` is SETNX-with-TTL) holds which command is in flight and when it
 * was requested. Laravel's cache lock only tracks owner/expiry — no payload — so
 * the record lives in the value instead of a separate mutex.
 *
 * Handing the command to Proxmox does not end the action, so the lock is not
 * released there — Proxmox hands back a task UPID and keeps working. The record
 * carries that UPID; resolve(), which the state endpoints call, asks Proxmox
 * whether the task has finished and releases the lock the moment it has. The TTL
 * is only a backstop: for a command that returned no UPID, or a task Proxmox can
 * no longer report on, the lock still self-clears after the window.
 */
class ServerPowerLockService
{
    /**
     * How long a single power action holds the lock. Sized to cover a typical
     * Proxmox power transition; a stuck action self-clears after this window.
     */
    public const TTL_SECONDS = 60;

    /**
     * How long a finished action's outcome lingers so the UI can turn its
     * in-progress toast into a success/failure message. Long enough to survive
     * the poll that observes the release (and a quick page reload right after),
     * short enough that a stale result never resurfaces on a later visit.
     */
    public const RESULT_TTL_SECONDS = 30;

    public function __construct(private ProxmoxActivityClient $activity) {}

    public function key(Server $server): string
    {
        return "server:{$server->id}:power-action";
    }

    public function resultKey(Server $server): string
    {
        return "server:{$server->id}:power-action:result";
    }

    /**
     * Atomically claim the lock for $command. Throws if another power action is
     * already in flight for this server.
     *
     * The task UPID isn't known yet — Proxmox only returns it once the command
     * is sent — so it is attached afterwards by attachTask(). The claim has to
     * happen first, before touching Proxmox, or two requests could both send.
     *
     * @throws PowerActionInProgressException
     */
    public function acquire(Server $server, PowerCommand $command): PendingPowerActionData
    {
        $pending = new PendingPowerActionData($command, now()->toIso8601String());

        $acquired = Cache::add($this->key($server), [
            'action' => $pending->toArray(),
            'upid' => null,
        ], self::TTL_SECONDS);

        if (! $acquired) {
            throw new PowerActionInProgressException;
        }

        // A new action supersedes the previous one's outcome; drop it so a stale
        // success/failure can't sit alongside the action now in flight.
        Cache::forget($this->resultKey($server));

        return $pending;
    }

    /**
     * Record the Proxmox task the sent command spawned, so resolve() can watch
     * it to completion. A no-op if the lock is already gone (released on a send
     * failure, or expired) — there is nothing to attach to.
     */
    public function attachTask(Server $server, ?string $upid): void
    {
        $record = Cache::get($this->key($server));

        if (! $record) {
            return;
        }

        Cache::put(
            $this->key($server),
            [...$record, 'upid' => $upid],
            self::TTL_SECONDS,
        );
    }

    public function pending(Server $server): ?PendingPowerActionData
    {
        $record = Cache::get($this->key($server));

        return $record ? $this->action($record) : null;
    }

    /**
     * Reconcile the held lock against the Proxmox task the command spawned,
     * releasing it once that task has finished.
     *
     * Returns the action still in flight, or null once nothing is pending —
     * which is what the state endpoints hand back to the UI. A finished task
     * releases the lock whether it succeeded or failed: either way the attempt
     * is over, and a failed one (a shutdown the guest refused) should stop
     * reading as "in progress".
     */
    public function resolve(Server $server): ?PendingPowerActionData
    {
        $record = Cache::get($this->key($server));

        if (! $record) {
            return null;
        }

        $pending = $this->action($record);
        $upid = $record['upid'] ?? null;

        // No task to watch — a command that returned no UPID, or a record left
        // by an older release. The TTL is the only way out.
        if (! is_string($upid)) {
            return $pending;
        }

        try {
            $task = $this->activity->setNode($server->node)->getStatus($upid);
        } catch (\Throwable) {
            // Proxmox is unreachable or has already rotated the task out of its
            // log. Don't fail the state read over it — leave the lock in place
            // and let the TTL clear it.
            return $pending;
        }

        // Still running: keep the controls locked.
        if ($task->status === TaskStatus::RUNNING) {
            return $pending;
        }

        // Finished (succeeded or failed): record the outcome for the UI to pick
        // up, then release so the controls unlock on the next poll.
        $this->recordResult($server, $pending, $task);
        $this->release($server);

        return null;
    }

    /**
     * The outcome of the most recently finished action, or null once it has
     * expired (or none has completed since the last acquire()).
     */
    public function result(Server $server): ?PowerActionResultData
    {
        $record = Cache::get($this->resultKey($server));

        return $record ? PowerActionResultData::from($record) : null;
    }

    public function release(Server $server): void
    {
        Cache::forget($this->key($server));
    }

    /**
     * Persist a finished task's outcome under the result key. `exitStatus` is
     * "OK"/"WARNINGS" on success and Proxmox's raw error string on failure; a
     * task that reports no exit status at all is treated as a plain success.
     */
    private function recordResult(Server $server, PendingPowerActionData $pending, TaskData $task): void
    {
        $exit = $task->exitStatus;

        $ok = $exit === null
            || $exit === TaskExitStatus::OK
            || $exit === TaskExitStatus::WARNINGS;

        $exitStatus = $exit instanceof TaskExitStatus ? $exit->value : $exit;

        $result = new PowerActionResultData(
            command: $pending->command,
            requestedAt: $pending->requestedAt,
            ok: $ok,
            exitStatus: $exitStatus,
        );

        Cache::put($this->resultKey($server), $result->toArray(), self::RESULT_TTL_SECONDS);
    }

    /**
     * The action out of a cache record. Falls back to reading the record itself
     * as the action so a lock written by an older release — which stored the
     * bare action, with no wrapper around it — is still understood for the
     * minute it takes to expire.
     */
    private function action(array $record): PendingPowerActionData
    {
        return PendingPowerActionData::from($record['action'] ?? $record);
    }
}
