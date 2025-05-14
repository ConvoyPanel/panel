<?php

namespace App\Http\Requests\Admin\AddressBlockGroups;

use App\Http\Requests\BaseApiRequest;
use App\Models\AddressBlockGroup;

class AddressBlockGroupRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return AddressBlockGroup::getRules();
    }
}
