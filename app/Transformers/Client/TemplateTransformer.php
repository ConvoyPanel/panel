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
            'hidden' => $template->hidden,
            'order_column' => $template->order_column,
        ];
    }
}
