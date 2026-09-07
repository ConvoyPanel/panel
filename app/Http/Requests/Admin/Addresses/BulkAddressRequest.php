<?php

namespace App\Http\Requests\Admin\Addresses;

use App\Data\Ipam\AddressMapData;
use App\Http\Requests\BaseApiRequest;

class BulkAddressRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'action' => 'required|string|in:reserve,release,delete',
            /*
             * Capped at the widest selection the UI can make: the map draws up to MAX_UNITS cells
             * and a single drag can take all of them. The first version of this capped at a page
             * of the table, which the map then broke on its first drag — the ceiling belongs to
             * the largest surface that can select, not the smallest.
             */
            'ids' => 'required|array|min:1|max:'.AddressMapData::MAX_UNITS,
            'ids.*' => 'required|integer',
        ];
    }
}
