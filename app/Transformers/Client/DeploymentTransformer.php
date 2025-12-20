<?php

namespace App\Transformers\Client;

use App\Models\Deployment;
use Illuminate\Support\Facades\Auth;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;

class DeploymentTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'template',
        'steps',
    ];

    protected array $availableIncludes = [
        'template',
        'steps',
    ];

    public function transform(Deployment $deployment): array
    {
        return [
            'id' => $deployment->id,
            'server_id' => $deployment->server_id,
            'template_id' => $deployment->template_id,
            'status' => $deployment->status,
            'type' => $deployment->type,
            'start_on_completion' => $deployment->start_on_completion,
            'requested_at' => $deployment->requested_at,
            'completed_at' => $deployment->completed_at,
        ];
    }

    public function includeTemplate(Deployment $deployment): ?Item
    {
        if (! $deployment->template) {
            return null;
        }

        return $this->item($deployment->template, new TemplateTransformer());
    }

    public function includeSteps(Deployment $deployment): Collection
    {
        return $this->collection($deployment->steps, new DeploymentStepTransformer());
    }
}
