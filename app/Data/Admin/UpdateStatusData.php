<?php

namespace App\Data\Admin;

use App\Enums\UpdateStatus;
use App\Services\Admin\UpdateCheckService;
use Spatie\LaravelData\Data;

/**
 * What the panel knows about its own version: what it is running, the newest
 * published release it has heard about, and when it last heard.
 *
 * Everything but `currentVersion` is nullable because a panel that has not
 * completed a check yet is a normal state, not an error — the fields are only
 * populated once {@see UpdateCheckService::check()} has
 * succeeded at least once.
 */
class UpdateStatusData extends Data
{
    public function __construct(
        public string $currentVersion,
        public ?string $latestVersion,
        public ?string $releaseUrl,
        public ?string $releasedAt,
        public ?string $checkedAt,
        /** The GitHub repository being watched, so the verdict names its source. */
        public string $repository,
        public bool $updateAvailable,
        public UpdateStatus $status,
    ) {}
}
