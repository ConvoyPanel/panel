<?php

namespace Convoy\Http\Requests\Admin\Nodes\Isos;

use Convoy\Http\Requests\Admin\AdminFormRequest;
use Convoy\Models\ISO;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIsoRequest extends AdminFormRequest
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
        $rules = ISO::getRulesForUpdate($this->parameter('iso', ISO::class));

        return [
            'name' => $rules['name'],
            'hidden' => $rules['hidden'],
        ];
    }
}
