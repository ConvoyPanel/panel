<?php

namespace Convoy\Http\Requests\Admin\Nodes\Addresses;

use Convoy\Http\Requests\Admin\AdminFormRequest;
use Convoy\Models\IPAddress;
use Illuminate\Support\Arr;

class UpdateAddressRequest extends AdminFormRequest
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
        return [
            ...Arr::except(IPAddress::getRulesForUpdate($this->parameter('address', IPAddress::class)), ['node_id']),
            'sync_server_config' => 'sometimes|boolean',
        ];
    }
}
