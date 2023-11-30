<?php

namespace Convoy\Http\Requests\Admin\Coterms;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Coterm;

class DeleteCotermRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->parameter('coterm', Coterm::class));
    }
}
