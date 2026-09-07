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

            /*
             * The square the user framed, in the source picture's own pixels.
             * All three or none -- a half-specified crop is a bug on the way
             * in, not something to guess the rest of. Bounds are checked
             * against the decoded image rather than here, since nothing at
             * this point knows how big it is.
             */
            'crop_x' => ['nullable', 'integer', 'min:0', 'required_with:crop_y,crop_size'],
            'crop_y' => ['nullable', 'integer', 'min:0', 'required_with:crop_x,crop_size'],
            'crop_size' => ['nullable', 'integer', 'min:1', 'required_with:crop_x,crop_y'],
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
