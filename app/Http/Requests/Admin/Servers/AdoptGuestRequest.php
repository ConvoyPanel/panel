<?php

namespace App\Http\Requests\Admin\Servers;

use App\Http\Requests\BaseApiRequest;

/**
 * Everything else about an adopted server is read off the guest, so the only
 * thing an operator has to supply is who owns it.
 */
class AdoptGuestRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'nullable|string|min:1|max:40',
            'hostname' => 'nullable|string|min:1|max:60',
        ];
    }
}
