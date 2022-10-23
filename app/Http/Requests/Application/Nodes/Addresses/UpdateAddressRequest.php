<?php

namespace Convoy\Http\Requests\Application\Nodes\Addresses;

use Convoy\Models\IPAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class UpdateAddressRequest extends FormRequest
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
     */
    public function rules()
    {
        $rules = IPAddress::getRulesForUpdate($this->parameter('address', IPAddress::class));

        return Arr::except($rules, 'node_id');
    }
}
