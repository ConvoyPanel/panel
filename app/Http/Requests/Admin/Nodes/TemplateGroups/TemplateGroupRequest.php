<?php

namespace App\Http\Requests\Admin\Nodes\TemplateGroups;

use App\Models\TemplateGroup;
use Illuminate\Foundation\Http\FormRequest;

class TemplateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = TemplateGroup::getRules();

        return [
            'name' => $rules['name'],
            'hidden' => $rules['hidden'],
        ];
    }
}
