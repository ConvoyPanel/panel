<?php

namespace App\Traits\Jobs;

use App\Enums\Server\DeploymentStatus;
use App\Models\DeploymentStep;
use Throwable;

/**
 * @property DeploymentStep $step
 */
trait FailsWithStep
{
    public function failed(?Throwable $exception): void
    {
        $this->step->update([
            'status' => DeploymentStatus::FAILED,
            'completed_at' => now(),
            'error_message' => $exception?->getMessage() ?? 'Unknown error',
        ]);
    }
}
