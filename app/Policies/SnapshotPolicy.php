<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SnapshotPolicy
{
    /**
     * Determine whether the user can create snapshots.
     */
    public function create(User $user, Server $server): Response|bool
    {
        if ($user->root_admin || $user->id === $server->user_id) {
            if ($server->snapshot_count_limit !== -1 && $server->snapshots()->count() >= $server->snapshot_count_limit) {
                return Response::deny('You have already created the maximum number of snapshots.');
            }

            if ($server->snapshot_size_limit !== -1 && $server->snapshots()->sum('size') >= $server->snapshot_size_limit) {
                return Response::deny('You have already ran out of snapshot space.');
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the snapshot.
     */
    public function restore(User $user, Snapshot $snapshot, Server $server = null): bool
    {
        if ($server && $snapshot->server_id !== $server->id) {
            return false;
        }

        return $user->root_admin || $user->id === $snapshot->server->user_id;
    }

    /**
     * Determine whether the user can delete the snapshot.
     */
    public function delete(User $user, Snapshot $snapshot, Server $server = null): bool
    {
        if ($server && $snapshot->server_id !== $server->id) {
            return false;
        }

        return $user->root_admin || $user->id === $snapshot->server->user_id;
    }
}
