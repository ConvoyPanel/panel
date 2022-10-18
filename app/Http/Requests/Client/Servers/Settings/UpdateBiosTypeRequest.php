<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Enums\Server\Cloudinit\BiosType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateBiosTypeRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'type' => [new Enum(BiosType::class), 'alpha_dash', 'required'],
        ];
    }
}
