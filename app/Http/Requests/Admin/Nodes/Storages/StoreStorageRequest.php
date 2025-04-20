<?php

namespace App\Http\Requests\Admin\Nodes\Storages;


use App\Models\Storage;
use App\Http\Requests\BaseApiRequest;

class StoreStorageRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return Storage::getRules();
    }
}
