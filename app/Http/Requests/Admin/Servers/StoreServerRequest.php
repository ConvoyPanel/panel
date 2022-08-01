<?php

namespace App\Http\Requests\Admin\Servers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $type
 */
class StoreServerRequest extends FormRequest
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
        return [
            'type' => 'in:new,existing|required',
            'name' => 'min:1|max:40',
            'node_id' => 'exists:nodes,id|required',
            'user_id' => 'exists:users,id|required',
            'vmid' => 'numeric|required_if:type,existing',
            'template_id' => 'exists:templates,id|required_if:type,new',
            'is_template' => 'boolean|required_if:type,existing',
            'is_visible' => 'boolean|required_with:is_template',
            'addresses' => 'array|max:2',
            'addresses.*' => 'exists:ip_addresses,id'
        ];
    }
}
