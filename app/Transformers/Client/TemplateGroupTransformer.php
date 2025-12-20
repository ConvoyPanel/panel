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
            'description' => $group->description,
            'icon' => $group->icon,
            'is_admin_only' => $group->is_admin_only,
        ];
    }

    public function includeTemplates(TemplateGroup $templateGroup)
    {
        return $this->collection($templateGroup->templates, new TemplateTransformer);
    }
}
