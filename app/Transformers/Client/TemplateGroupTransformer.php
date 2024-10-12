<?php

namespace App\Transformers\Client;

use App\Models\TemplateGroup;
use League\Fractal\TransformerAbstract;

class TemplateGroupTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'templates',
    ];

    protected array $availableIncludes = [
        'templates',
    ];

    /**
     * A Fractal transformer.
     */
    public function transform(TemplateGroup $group): array
    {
        return [
            'uuid' => $group->uuid,
            'name' => $group->name,
            'hidden' => $group->hidden,
            'order_column' => $group->order_column,
        ];
    }

    public function includeTemplates(TemplateGroup $templateGroup)
    {
        return $this->collection($templateGroup->templates, new TemplateTransformer);
    }
}
