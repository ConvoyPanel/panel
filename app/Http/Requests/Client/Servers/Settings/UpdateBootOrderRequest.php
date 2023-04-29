<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBootOrderRequest extends FormRequest
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
        return [
            'order' => 'array|present',
            'order.*' => 'string',
        ];
    }
}
