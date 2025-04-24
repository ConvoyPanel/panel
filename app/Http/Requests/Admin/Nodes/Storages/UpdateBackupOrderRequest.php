<?php

namespace App\Http\Requests\Admin\Nodes\Storages;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Foundation\Http\FormRequest;

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
            ],
        ];
    }
}
