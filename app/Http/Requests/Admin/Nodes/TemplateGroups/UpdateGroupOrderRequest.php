<?php

namespace App\Http\Requests\Admin\Nodes\TemplateGroups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateGroupOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:template_groups,id',
        ];
    }

    public function withValidator(Validator $validator)
    {
        // validate if each order id is unique in the array
        $validator->after(function ($validator) {
            if (count($this->order) !== count(array_unique($this->order))) {
                $validator->errors()->add('order', 'Duplicate order id');
            }
        });
    }
}
