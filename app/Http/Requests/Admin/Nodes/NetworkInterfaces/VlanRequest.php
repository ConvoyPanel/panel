<?php

namespace App\Http\Requests\Admin\Nodes\NetworkInterfaces;

use App\Http\Requests\BaseApiRequest;
use App\Models\NetworkInterface;
use App\Models\Vlan;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VlanRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Vlan::getRules();

        // A tag identifies a VLAN within its bridge, so uniqueness is scoped
        // to the interface — the same tag on two different bridges is two
        // unrelated VLANs, which is normal.
        $rules['tag'][] = Rule::unique('vlans', 'tag')
            ->where('network_interface_id', $this->networkInterface()->id)
            ->ignore($this->route('vlan'));

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->networkInterface()->is_vlan_aware) {
                    $validator->errors()->add(
                        'tag',
                        'The network interface must be marked VLAN-aware before declaring VLANs on it.',
                    );
                }
            },
        ];
    }

    private function networkInterface(): NetworkInterface
    {
        return $this->parameter('network_interface', NetworkInterface::class);
    }
}
