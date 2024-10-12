<?php

namespace App\Http\Requests\Client\Servers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNetworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateNetworkSettings', $this->route('server'));
    }

    public function rules(): array
    {
        return [
            'nameservers' => ['array', 'present'],
            'nameservers.*' => ['string', 'ip'],
        ];
    }
}
