<?php

namespace App\Http\Requests\Admin\Images;

use App\Enums\ImageIcon;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rules\Enum;

class ImageGroupRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:40',
            'description' => 'nullable|string|max:500',
            'icon' => ['nullable', new Enum(ImageIcon::class)],
            'is_admin_only' => 'required|boolean',
        ];
    }
}
