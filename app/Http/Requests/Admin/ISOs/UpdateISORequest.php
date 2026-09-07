<?php

namespace App\Http\Requests\Admin\ISOs;

use App\Http\Requests\BaseApiRequest;
use App\Models\ISO;
use Illuminate\Support\Arr;

/**
 * Only the label and its visibility are editable.
 *
 * The source and the file name are what nodes have already fetched against;
 * changing either would leave copies on disk that no longer match the record.
 * A different file is a different ISO.
 */
class UpdateISORequest extends BaseApiRequest
{
    public function rules(): array
    {
        return Arr::only(ISO::getRules(), ['name', 'hidden']);
    }
}
