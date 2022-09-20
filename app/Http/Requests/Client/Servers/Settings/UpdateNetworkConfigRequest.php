<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Rules\Network\Hostname;
use Illuminate\Foundation\Http\FormRequest;
use Convoy\Rules\Network\Domain;

class UpdateNetworkConfigRequest extends FormRequest
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
            'hostname' => [new Hostname, 'nullable'],
            'nameservers' => ['array', 'nullable'],
            'nameservers.*' => ['string', new Domain, 'nullable'],
        ];
    }
}
