<?php

namespace Convoy\Http\Requests\Client\Servers;

use Convoy\Enums\Server\PowerAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SendPowerCommandRequest extends FormRequest
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
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state' => ['required', new Enum(PowerAction::class)],
        ];
    }
}
