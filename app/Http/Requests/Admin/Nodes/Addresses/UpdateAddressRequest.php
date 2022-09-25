<?php

namespace Convoy\Http\Requests\Admin\Nodes\Addresses;

use Convoy\Enums\Network\AddressType;
use Convoy\Models\IPAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return IPAddress::getRulesForUpdate($this->parameter('address', IPAddress::class));
    }
}
