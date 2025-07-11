<?php

namespace App\Http\Requests\Admin\Nodes\TemplateGroups;

use App\Enums\TemplateIcon;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rules\Enum;

class TemplateGroupRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:40',
            'description' => 'nullable|string|max:500',
            'icon' => ['nullable', new Enum(TemplateIcon::class)],
            'is_admin_only' => 'required|boolean',
        ];
    }
}
