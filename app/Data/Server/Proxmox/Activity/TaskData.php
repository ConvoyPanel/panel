<?php

namespace App\Data\Server\Proxmox\Activity;

use App\Enums\Activity\TaskExitStatus;
use App\Enums\Activity\TaskStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

class TaskData extends Data
{
    public function __construct(
        public string $uniqueProcessId,
        public string $node,
        public int $processId,
        /**
         * @var int $processStartTime
         *          Process start time (ticks/internal).
         */
        public int $processStartTime,
        public CarbonImmutable $startTime,
        public ?CarbonImmutable $endTime,
        public string $type,
        public string $targetId,
        public string $user,
        public ?TaskStatus $status,
        public TaskExitStatus|string|null $exitStatus,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $get = fn (string $key, $default = null) => Arr::get($raw, $key, $default);

        $exitStatus = $get('exitstatus');

        return new self(
            uniqueProcessId: $get('upid'),
            node: $get('node'),
            processId: (int) $get('pid'),
            processStartTime: (int) $get('pstart'),
            startTime: CarbonImmutable::createFromTimestamp($get('starttime')),
            endTime: $get('endtime') ? CarbonImmutable::createFromTimestamp($get('endtime')) : null,
            type: $get('type'),
            targetId: $get('id'),
            user: $get('user'),
            // A running task reports neither field yet; guard the nulls rather
            // than pass them to tryFrom(), which only accepts string|int.
            status: $get('status') !== null ? TaskStatus::tryFrom($get('status')) : null,
            exitStatus: $exitStatus !== null ? (TaskExitStatus::tryFrom($exitStatus) ?? $exitStatus) : null,
        );
    }
}
