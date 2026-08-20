<?php

namespace App\Traits;

use App\Exceptions\Proxmox\RequestException;
use App\Models\Server;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait HandlesProxmoxErrors
{
    /**
     * Determine if a "does not exist" error should be ignored.
     */
    protected function isNonexistentVMError(RequestException $e, string $message = 'does not exist'): bool
    {
        if (Str::contains(Arr::get($e->response->json(), 'message', ''), $message)) {
            return true;
        }

        return false;
    }

    /**
     * Leave a trace when a nonexistent-VM error is treated as success. Usually
     * the VM really is gone and this line is noise -- but "does not exist" is
     * also exactly what the *recorded* node answers after PVE moved the guest
     * elsewhere (HA recovery, migration), in which case the action silently
     * did nothing. The placement reconciler re-homes the row within a poll
     * cycle; this line is what connects the two events in the log.
     */
    protected function logSwallowedNonexistentVM(Server $server, string $action): void
    {
        Log::warning('Treated nonexistent-VM error as success; if the guest was migrated off this node, the action did not reach it', [
            'server' => $server->id,
            'vmid' => $server->vmid,
            'node' => $server->node->name,
            'action' => $action,
        ]);
    }
}
