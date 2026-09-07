<?php

namespace App\Http\Requests\Admin\Addresses;

use App\Http\Requests\BaseApiRequest;

class BulkAddressRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'action' => 'required|string|in:reserve,release,delete',
            // Capped at a page of the table. A selection is made by hand out of what is on screen,
            // so a larger list is a malformed client rather than a real operator action.
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'required|integer',
        ];
    }
}
