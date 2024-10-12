<?php

namespace App\Transformers\Admin;

use App\Models\TemplateGroup;
use League\Fractal\TransformerAbstract;

class TemplateGroupTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'templates',
    ];

    /**
     * A Fractal transformer.
     */
    public function transform(TemplateGroup $templateGroup): array
    {
        return [
            'id' => $templateGroup->id,
            'node_id' => $templateGroup->node_id,
            'uuid' => $templateGroup->uuid,
            'name' => $templateGroup->name,
            'hidden' => $templateGroup->hidden,
            'order_column' => $templateGroup->order_column,
        ];
    }

    public function includeTemplates(TemplateGroup $templateGroup)
    {
        return $this->collection($templateGroup->templates, new TemplateTransformer);
    }
}
