<?php

namespace Convoy\Http\Requests\Admin\Coterms;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Coterm;
use Illuminate\Support\Arr;

class StoreCotermRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Coterm::getRules();

        return Arr::only($rules, ['name', 'is_tls_enabled', 'fqdn', 'port']);
    }
}
