<?php

namespace App\Http\Requests\Admin\Nodes\NetworkInterfaces;


use Illuminate\Support\Arr;
use App\Models\NetworkInterface;
use App\Http\Requests\BaseApiRequest;

class NetworkInterfaceRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return Arr::except(NetworkInterface::getRules(), ['node_id']);
    }
}
