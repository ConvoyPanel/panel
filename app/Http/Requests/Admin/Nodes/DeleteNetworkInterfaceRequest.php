<?php

namespace App\Http\Requests\Admin\Nodes;

use App\Models\NetworkInterface;
use Illuminate\Validation\Validator;
use App\Http\Requests\BaseApiRequest;

class DeleteNetworkInterfaceRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->parameter('networkInterface', NetworkInterface::class));
    }

    public function rules(): array
    {
        return [
            //
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var NetworkInterface $networkInterface */
                $networkInterface = $this->parameter('networkInterface', NetworkInterface::class);
                $nodeId = $networkInterface->node_id;

                $isInUse = $networkInterface->addressBlockGroups()
                    ->whereHas('addressBlocks.addresses', function ($query) use ($nodeId) {
                        $query->whereNotNull('server_id')
                            ->whereHas('server', function ($serverQuery) use ($nodeId) {
                                $serverQuery->where('node_id', $nodeId);
                            });
                    })
                    ->exists();

                if ($isInUse) {
                    $validator->errors()->add(
                        'network_interface',
                        'This network interface cannot be deleted because it is in use by one or more servers.'
                    );
                }
            },
        ];
    }
}
