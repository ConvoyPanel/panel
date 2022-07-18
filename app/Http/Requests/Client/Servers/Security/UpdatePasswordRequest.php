<?php

namespace App\Http\Requests\Client\Servers\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Servers\Cloudinit\AuthenticationType;

class UpdatePasswordRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'type' => [new Enum(AuthenticationType::class), 'required'],
            'password' => ['required', 'confirmed', 'max:255', 'min:10'],
        ];
    }
}