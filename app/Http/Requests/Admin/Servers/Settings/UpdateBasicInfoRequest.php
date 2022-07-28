<?php

namespace App\Http\Requests\Admin\Servers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBasicInfoRequest extends FormRequest
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
            'name' => 'min:1|max:40',
            'node_id' => 'exists:nodes,id|required',
            'user_id' => 'exists:users,id|required',
            'vmid' => 'numeric|required',
            'is_template' => 'boolean|required',
            'is_visible' => 'boolean|required_with:is_template'
        ];
    }
}
