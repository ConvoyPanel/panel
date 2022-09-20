<?php

namespace Convoy\Http\Requests\Admin\Servers;

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
        $rules = [
            'type' => 'in:new,existing|required',
            'name' => 'min:1|max:40',
            'node_id' => 'exists:nodes,id|required',
            'user_id' => 'exists:users,id|required',
            'vmid' => 'numeric|required_if:type,existing',
            'addresses' => 'array|max:2',
            'addresses.*' => 'exists:ip_addresses,id'
        ];

        if ($this->request->get('type') === 'new')
        {
            $rules['template_id'] = 'exists:templates,id|required';
            $rules['cpu'] = 'numeric|min:1|required';
            $rules['memory'] = 'numeric|min:16777216|required';
            $rules['disk'] = 'numeric|min:1|required';
        }

        if ($this->request->get('type') === 'existing')
        {
            $rules['is_template'] = 'boolean|required';
            $rules['is_visible'] = 'boolean|required_with:is_template';
        }

        return $rules;
    }
}
