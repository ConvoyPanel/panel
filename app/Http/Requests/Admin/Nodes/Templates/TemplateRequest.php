<?php

namespace App\Http\Requests\Admin\Nodes\Templates;

use App\Http\Requests\BaseApiRequest;
use App\Models\Template;
use Illuminate\Support\Arr;

class TemplateRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Template::getRules();

        return Arr::only($rules, ['name', 'description', 'vmid', 'is_admin_only']);
    }
}
