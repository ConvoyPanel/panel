<?php

namespace App\Http\Requests\Anchor;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:191'],
            'mode' => ['required', 'string', 'in:agent,relay'],
            'protocol.min' => ['required', 'integer', 'min:1'],
            'protocol.max' => ['required', 'integer', 'gte:protocol.min'],
            'capabilities' => ['required', 'array'],
            'capabilities.*' => ['string', 'max:191'],
        ];
    }
}
