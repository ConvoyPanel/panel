<?php

namespace App\Traits\Jobs;

use App\Models\DeploymentStep;
use Throwable;

/**
 * @property DeploymentStep $step
 */
trait FailsWithStep
{
    /**
     * Laravel calls this once, after a job's retries are exhausted. The step's
     * own guarded transition decides what that means: a step that already
     * completed on a successful attempt stays completed.
     */
    public function failed(?Throwable $exception): void
    {
        $this->step->markFailed($exception);
    }
}
