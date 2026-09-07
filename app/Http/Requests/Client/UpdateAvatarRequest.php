<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /*
             * A ceiling on what the panel is willing to decode, not on what the
             * user may choose: a phone photo is a couple of megabytes and the
             * stored result is the same ~30KB either way. `image` reads the
             * file's header rather than its name, and the service re-checks the
             * format it actually got before handing anything to GD.
             */
            'avatar' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.max' => 'Pictures must be under 10 MB.',
            'avatar.mimes' => 'Use a JPEG, PNG, WebP or GIF.',
        ];
    }
}
