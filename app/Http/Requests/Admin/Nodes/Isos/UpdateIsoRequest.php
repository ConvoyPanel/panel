<?php

namespace App\Http\Requests\Admin\Nodes\Isos;

use App\Http\Requests\BaseApiRequest;
use App\Models\ISO;

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
