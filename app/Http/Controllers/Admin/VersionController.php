<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\UpdateStatusData;
use App\Exceptions\Http\Admin\UpdateCheckFailedException;
use App\Services\Admin\UpdateCheckService;

class VersionController
{
    /**
     * Reads the last completed check. Never reaches out to GitHub itself — the
     * scheduled `updates:check` command is what refreshes this.
     */
    public function show(UpdateCheckService $updates): UpdateStatusData
    {
        return $updates->status();
    }

    /**
     * Checks now, on an admin's explicit request, rather than waiting for the
     * next scheduled pass. This is the one path that fetches during a request;
     * it is rate limited on the route because the ceiling that matters is
     * GitHub's, not ours.
     *
     * @throws UpdateCheckFailedException
     */
    public function check(UpdateCheckService $updates): UpdateStatusData
    {
        return $updates->check();
    }
}
