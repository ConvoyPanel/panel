<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNetworkRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nameservers' => ['array', 'present'],
            'nameservers.*' => ['string', 'ip'],
        ];
    }
}
