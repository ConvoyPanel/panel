<?php

namespace App\Http\Requests\Admin\Nodes\Templates;

use App\Models\Template;
use Illuminate\Foundation\Http\FormRequest;

class TemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = Template::getRules();

        return [
            'name' => $rules['name'],
            'vmid' => $rules['vmid'],
            'hidden' => $rules['hidden'],
        ];
    }
}
