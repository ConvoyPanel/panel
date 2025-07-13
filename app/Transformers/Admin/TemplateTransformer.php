<?php

namespace App\Transformers\Admin;

use App\Models\Template;
use League\Fractal\TransformerAbstract;

class TemplateTransformer extends TransformerAbstract
{
    public function transform(Template $template): array
    {
        return [
            'uuid' => $template->uuid,
            'name' => $template->name,
            'description' => $template->description,
            'vmid' => $template->vmid,
            'is_admin_only' => $template->is_admin_only,
        ];
    }
}
