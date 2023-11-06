<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Enums\Server\BiosType;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBiosTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => [new Enum(BiosType::class), 'alpha_dash', 'required'],
        ];
    }
}
