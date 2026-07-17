<?php

namespace App\Data\Server\Deployments;

use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\ProgressMode;
use App\Models\DeploymentStep;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class DeploymentStepData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public DeploymentStatus $status,
        public ProgressMode $progressMode,
        public int $sequence,
        public ?int $progressCurrent,
        public ?int $progressTotal,
        public ?CarbonImmutable $startedAt,
        public ?CarbonImmutable $completedAt,
        public ?string $errorCode,
        public ?string $errorMessage,
    ) {}

    public static function fromModel(DeploymentStep $step): self
    {
        $isAdmin = (bool) Auth::user()?->root_admin;

        return new self(
            id: $step->id,
            name: $step->name,
            status: $step->status,
            progressMode: $step->progress_mode,
            sequence: $step->sequence,
            progressCurrent: $step->progress_current,
            progressTotal: $step->progress_total,
            startedAt: $step->started_at
                ? CarbonImmutable::parse($step->started_at)
                : null,
            completedAt: $step->completed_at
                ? CarbonImmutable::parse($step->completed_at)
                : null,
            errorCode: $isAdmin ? $step->error_code : null,
            errorMessage: $isAdmin ? $step->error_message : null,
        );
    }
}
