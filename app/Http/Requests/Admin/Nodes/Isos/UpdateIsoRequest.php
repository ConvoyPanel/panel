<?php

namespace Convoy\Http\Requests\Admin\Nodes\Isos;

use Convoy\Models\ISO;
use Convoy\Http\Requests\FormRequest;

class UpdateIsoRequest extends FormRequest
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
