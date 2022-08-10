<?php

namespace App\Http\Requests\Application\Servers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetailsRequest extends FormRequest
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
            'limits' => 'sometimes|array|required',
            'limits.cpu' => 'sometimes|numeric|min:1|required',
            'limits.memory' => 'sometimes|numeric|min:16777216|required',
            'limits.disk' => 'sometimes|numeric|min:1|required',
            'limits.address_ids' => 'sometimes|numeric|exists:ip_addresses,id|required'
        ];
    }
}
