<?php

namespace Convoy\Http\Requests\Application\Nodes\Addresses;

use Convoy\Models\IPAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class StoreAddressRequest extends FormRequest
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
        $rules = IPAddress::getRules();

        return Arr::except($rules, 'node_id');
    }
}
