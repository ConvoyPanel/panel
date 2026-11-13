<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\Template;
use League\Fractal\TransformerAbstract;

class TemplateTransformer extends TransformerAbstract
{
    public function transform(Template $template)
    {
        return [
            'uuid' => $template->uuid,
            'name' => $template->name,
            // 0/1, as the API sent it from MySQL's tinyint. Postgres returns a real
            // boolean, and integrations may compare with === 1.
            'hidden' => (int) $template->hidden,
            'order_column' => $template->order_column,
        ];
    }
}
