<?php

namespace App\Http\Requests\Admin\Nodes\Templates;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTemplateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:templates,id',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (count($this->order) !== count(array_unique($this->order))) {
                    $validator->errors()->add('order', 'Duplicate order id');
                }
            },
        ];
    }
}
