<?php

namespace App\Transformers\Admin;

use App\Models\TemplateGroup;
use League\Fractal\TransformerAbstract;
use League\Fractal\Resource\Collection;

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
            'uuid' => $templateGroup->uuid,
            'name' => $templateGroup->name,
            'description' => $templateGroup->description,
            'icon' => $templateGroup->icon,
            'isAdminOnly' => $templateGroup->is_admin_only,
        ];
    }

    public function includeTemplates(TemplateGroup $templateGroup): Collection
    {
        return $this->collection($templateGroup->templates, new TemplateTransformer);
    }
}
