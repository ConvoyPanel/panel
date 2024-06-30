<?php

namespace Convoy\Http\Requests\Admin\Coterms;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Coterm;
use Convoy\Rules\Fqdn;
use Illuminate\Support\Arr;

class StoreCotermRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Coterm::getRules();
        $rules['fqdn'][] = Fqdn::make();

        return [
            ...Arr::only($rules, ['name', 'is_tls_enabled', 'fqdn', 'port']),
            'node_ids' => ['nullable', 'array'],
            'node_ids.*' => ['required', 'integer', 'exists:nodes,id'],
        ];
    }
}
