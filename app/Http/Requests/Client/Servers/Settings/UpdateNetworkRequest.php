<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Rules\Network\Domain;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNetworkRequest extends FormRequest
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
            'nameservers' => ['array', 'required'],
            'nameservers.*' => ['string', new Domain, 'nullable'],
        ];
    }
}
