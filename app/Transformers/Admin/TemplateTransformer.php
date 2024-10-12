<?php

namespace App\Transformers\Admin;

use App\Models\Template;
use League\Fractal\TransformerAbstract;

class TemplateTransformer extends TransformerAbstract
{
    public function transform(Template $template)
    {
        return [
            'id' => $template->id,
            'template_group_id' => $template->template_group_id,
            'uuid' => $template->uuid,
            'name' => $template->name,
            'vmid' => $template->vmid,
            'hidden' => $template->hidden,
            'order_column' => $template->order_column,
        ];
    }
}
