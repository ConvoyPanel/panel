<?php

namespace App\Http\Requests\Application\Nodes\Addresses;

use App\Enums\Network\AddressType;
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
     * @return array{
     *     server_id: int | null,
     *     node_id: int,
     *     address: string,
     *     cidr: int,
     *     gateway: string,
     *     type: string
     */
    public function rules()
    {
        return [
            'server_id' => 'exists:servers,id|nullable',
            'node_id' => 'required|exists:nodes,id',
            'type' => [new Enum(AddressType::class), 'required'],
            'address' => 'ip',
            'cidr' => 'numeric|required',
            'gateway' => 'ip',
            'mac_address' => 'mac_address|nullable',
        ];
    }
}
