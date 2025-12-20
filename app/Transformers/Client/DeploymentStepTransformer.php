<?php

namespace App\Transformers\Client;

use App\Models\DeploymentStep;
use Illuminate\Support\Facades\Auth;
use League\Fractal\TransformerAbstract;

class DeploymentStepTransformer extends TransformerAbstract
{
    public function transform(DeploymentStep $step): array
    {
        return [
            'id' => $step->id,
            'name' => $step->name,
            'status' => $step->status,
            'progress_current' => $step->progress_current,
            'progress_total' => $step->progress_total,
            'started_at' => $step->started_at,
            'completed_at' => $step->completed_at,
            'error_code' => Auth::user()->root_admin ? $step->error_code : null,
            'error_message' => Auth::user()->root_admin ? $step->error_message : null,
        ];
    }
}
