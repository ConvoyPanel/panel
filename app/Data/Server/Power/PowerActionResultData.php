<?php

namespace App\Data\Server\Power;

use App\Enums\Server\PowerCommand;
use Spatie\LaravelData\Data;

/**
 * The outcome of the most recently finished power action, exposed on the server
 * state response for the short window after it completes so the UI can resolve
 * its in-progress toast into a success or failure message.
 *
 * `requestedAt` mirrors the value from the PendingPowerActionData the action was
 * held under, so the frontend can correlate this result with the specific action
 * it was showing as pending and ignore a stale result left by an earlier one.
 *
 * `exitStatus` is Proxmox's raw task exit string when the task failed (e.g. a
 * shutdown the guest refused) — kept verbatim so the UI can surface it as
 * detail — and normalised to the enum value ("OK"/"WARNINGS") on success.
 */
class PowerActionResultData extends Data
{
    public function __construct(
        public PowerCommand $command,
        public string $requestedAt,
        public bool $ok,
        public ?string $exitStatus,
    ) {}
}
