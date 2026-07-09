<?php

namespace App\Http\Requests\Admin\Nodes\NetworkInterfaces;

use App\Http\Requests\BaseApiRequest;
use App\Models\NetworkInterface;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class NetworkInterfaceRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return Arr::except(NetworkInterface::getRules(), ['node_id']);
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $networkInterface = $this->route('network_interface');
                $isVlanAware = $this->has('is_vlan_aware')
                    ? $this->boolean('is_vlan_aware')
                    : ($networkInterface instanceof NetworkInterface && $networkInterface->is_vlan_aware);

                if ($this->filled('vlan_tag') && ! $isVlanAware) {
                    $validator->errors()->add(
                        'vlan_tag',
                        'The network interface must be marked VLAN-aware before assigning a VLAN tag.',
                    );
                }
            },
        ];
    }
}
