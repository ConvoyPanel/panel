<?php

namespace Convoy\Http\Requests\Admin\Nodes\Isos;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\ISO;

class UpdateIsoRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = ISO::getRulesForUpdate($this->parameter('iso', ISO::class));

        return [
            'name' => $rules['name'],
            'hidden' => $rules['hidden'],
        ];
    }
}
