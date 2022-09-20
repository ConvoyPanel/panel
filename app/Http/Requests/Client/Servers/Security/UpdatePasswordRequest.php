<?php

namespace Convoy\Http\Requests\Client\Servers\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Convoy\Enums\Servers\Cloudinit\AuthenticationType;

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
        $rules = [
            'type' => [new Enum(AuthenticationType::class), 'required'],
        ];

        if ($this->request->get('type') === 'sshkeys')
            $rules['contents'] = ['required_if:type,sshkeys'];
        if ($this->request->get('type') === 'cipassword')
            $rules['password'] = ['confirmed', 'min:10', 'required_if:type,cipassword'];

        return $rules;
    }
}