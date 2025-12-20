<?php

namespace App\Transformers\Client;

use App\Models\Template;
use League\Fractal\TransformerAbstract;

class TemplateTransformer extends TransformerAbstract
{
    public function transform(Template $template)
    {
        return [
            'uuid' => $template->uuid,
            'template_group_id' => $template->template_group_id,
            'name' => $template->name,
            'description' => $template->description,
            'vmid' => $template->vmid,
            'is_admin_only' => $template->is_admin_only,
        ];
    }
}
