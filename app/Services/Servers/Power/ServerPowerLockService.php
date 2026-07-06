<?php

namespace App\Services\Servers\Power;

use App\Data\Server\Power\PendingPowerActionData;
use App\Enums\Server\PowerCommand;
use App\Exceptions\Http\Server\PowerActionInProgressException;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;

/**
 * Guards interactive power commands with a short-lived per-server lock so a user
 * cannot spam start/stop/reboot and enqueue conflicting Proxmox tasks.
 *
 * The lock doubles as the "pending action" record: a single atomic cache entry
 * (`Cache::add` is SETNX-with-TTL) holds which command is in flight and when it
 * was requested. Laravel's cache lock only tracks owner/expiry — no payload — so
 * the record lives in the value instead of a separate mutex. The TTL is a safety
 * net that auto-clears the lock if a request dies before releasing it.
 */
class ServerPowerLockService
{
    /**
     * How long a single power action holds the lock. Sized to cover a typical
     * Proxmox power transition; a stuck action self-clears after this window.
     */
    public const TTL_SECONDS = 60;

    public function key(Server $server): string
    {
        return "server:{$server->id}:power-action";
    }

    /**
     * Atomically claim the lock for $command. Throws if another power action is
     * already in flight for this server.
     *
     * @throws PowerActionInProgressException
     */
    public function acquire(Server $server, PowerCommand $command): PendingPowerActionData
    {
        $pending = new PendingPowerActionData($command, now()->toIso8601String());

        $acquired = Cache::add($this->key($server), $pending->toArray(), self::TTL_SECONDS);

        if (! $acquired) {
            throw new PowerActionInProgressException();
        }

        return $pending;
    }

    public function pending(Server $server): ?PendingPowerActionData
    {
        $raw = Cache::get($this->key($server));

        return $raw ? PendingPowerActionData::from($raw) : null;
    }

    public function release(Server $server): void
    {
        Cache::forget($this->key($server));
    }
}
