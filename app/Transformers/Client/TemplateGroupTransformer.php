<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\TemplateGroup;
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
            // 0/1, as the API sent it from MySQL's tinyint. Postgres returns a real
            // boolean, and integrations may compare with === 1.
            'hidden' => (int) $group->hidden,
            'order_column' => $group->order_column,
        ];
    }

    public function includeTemplates(TemplateGroup $templateGroup)
    {
        return $this->collection($templateGroup->templates, new TemplateTransformer);
    }
}
