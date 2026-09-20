<?php

namespace App\Http\Requests\Admin\Servers;

use App\Http\Requests\BaseApiRequest;

class MigrateServerRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'node_id' => 'required|integer|exists:nodes,id',
            /*
             * The operator saying, in as many words, that the guest's IP is
             * about to change. Ignored for a preserving migration, and refused
             * without it for a reallocating one -- the panel computes the
             * disposition, so this is an acknowledgement rather than a choice.
             */
            'acknowledge_address_change' => 'sometimes|boolean',
        ];
    }
}
