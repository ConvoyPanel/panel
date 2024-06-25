<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\Deployment;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;

class DeploymentTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'template',
    ];

    protected array $availableIncludes = [
        'template',
    ];

    public function transform(Deployment $deployment): array
    {
        return [
            'id' => $deployment->id,
            'server_id' => $deployment->server_id,
            'template_id' => $deployment->template_id,
            'should_create_vm' => $deployment->should_create_vm,
            'start_on_completion' => $deployment->start_on_completion,
            'build_successful' => $deployment->build_successful,
            'build_progress' => $deployment->build_progress,
            'built_vm_at' => $deployment->built_vm_at,
            'sync_successful' => $deployment->sync_successful,
            'synced_vm_at' => $deployment->synced_vm_at,
            'created_at' => $deployment->created_at,
        ];
    }

    public function includeTemplate(Deployment $deployment): Item
    {
        return $this->item($deployment->template, new TemplateTransformer());
    }
}
