<?php

namespace App\Http\Requests\Anchor;

use Illuminate\Foundation\Http\FormRequest;

class ConsumeEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['token' => ['required', 'string', 'max:255']];
    }
}
