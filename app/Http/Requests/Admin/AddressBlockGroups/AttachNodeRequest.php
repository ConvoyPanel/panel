<?php

namespace App\Http\Requests\Admin\AddressBlockGroups;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AddressBlockGroup;

class AttachNodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('attachNode', $this->route('address_block_group'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'network_interface_id' => 'required|integer|exists:network_interfaces,id',
        ];
    }
}
