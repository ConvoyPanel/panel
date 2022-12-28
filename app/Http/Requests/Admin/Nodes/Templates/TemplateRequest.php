<?php

namespace Convoy\Http\Requests\Admin\Nodes\Templates;

use Convoy\Models\Template;
use Illuminate\Foundation\Http\FormRequest;

class TemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = Template::getRules();

        return [
            'name' => $rules['name'],
            'vmid' => $rules['vmid'],
            'hidden' => $rules['hidden'],
        ];
    }
}
