<?php

namespace App\Http\Requests\Admin\Coterms;

use App\Http\Requests\BaseApiRequest;
use App\Models\Coterm;

class DeleteCotermRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->parameter('coterm', Coterm::class));
    }
}
