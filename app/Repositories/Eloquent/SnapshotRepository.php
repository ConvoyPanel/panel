<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\Repository\Eloquent\TooManyRootSnapshotsException;
use App\Models\Snapshot;
use Illuminate\Support\Arr;

class SnapshotRepository
{
    public function buildSnapshotTree($snapshots, $parentId = null): Snapshot|array|null
    {
        $branch = [];

        foreach ($snapshots as $snapshot) {
            if ($snapshot->snapshot_id == $parentId) {
                // Recursively build the tree for child snapshots
                $children = $this->buildSnapshotTree($snapshots, $snapshot->id);
                if ($children) {
                    $snapshot->children = $children; // Add children to the current snapshot
                } else {
                    $snapshot->children = []; // Ensure children is an array
                }
                $branch[] = $snapshot; // Add current snapshot to branch
            }
        }

        if (is_null($parentId) && count($branch) > 1) {
            throw new TooManyRootSnapshotsException();
        }

        return is_null($parentId) ? Arr::first($branch) : $branch; // Return the constructed tree
    }
}
