<?php

namespace App\Http\Requests\Admin\Nodes\Addresses;

use App\Enums\Network\AddressType;
use App\Models\IPAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
        return IPAddress::getRules();
    }
}
