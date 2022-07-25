<?php

namespace App\Http\Requests\Client\Servers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ReinstallServerRequest extends FormRequest
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
            'template_id' => 'exists:templates,id'
        ];
    }
}
