<?php

namespace App\Http\Requests\Admin\Nodes\Storages;

use App\Enums\Node\Storage\StorageContentType;
use App\Http\Requests\BaseApiRequest;
use App\Rules\StorageAllows;

class UpdateBackupOrderRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'ids' => [
                'required',
                'array',
                'min:1',
            ],
            'ids.*' => [
                'required',
                'integer',
                'exists:storages,id',
                new StorageAllows(StorageContentType::BACKUPS),
            ],
        ];
    }
}
