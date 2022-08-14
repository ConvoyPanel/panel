<?php

namespace App\Http\Requests\Application\Servers;

use Illuminate\Foundation\Http\FormRequest;

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
        ];

        if ($this->request->get('type') === 'new')
        {
            $rules['template_id'] = 'exists:templates,id|required';
            $rules['vmid'] = 'sometimes|numeric|min:100|max:999999999|required';
            $rules['limits'] = 'array|required';
            $rules['limits.cpu'] = 'numeric|min:1|required';
            $rules['limits.memory'] = 'numeric|min:16777216|required';
            $rules['limits.disk'] = 'numeric|min:1|required';
            $rules['limits.address_ids'] = 'numeric|exists:ip_addresses,id|required';
        }

        if ($this->request->get('type') === 'existing')
        {
            $rules['configuration.template'] = 'sometimes|boolean';
            $rules['configuration.visible'] = 'sometimes|boolean';
            $rules['vmid'] = 'numeric|min:100|max:999999999|required';
        }

        return $rules;
    }
}
