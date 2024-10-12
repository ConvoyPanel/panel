<?php

namespace App\Http\Requests\Admin\Coterms;

use App\Http\Requests\BaseApiRequest;
use App\Models\Coterm;
use Illuminate\Support\Arr;

class UpdateCotermRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $coterm = $this->parameter('coterm', Coterm::class);
        $rules = Coterm::getRulesForUpdate($coterm);

        return [
            ...Arr::only($rules, ['name', 'is_tls_enabled', 'fqdn', 'port']),
            'node_ids' => ['nullable', 'array'],
            'node_ids.*' => ['required', 'integer', 'exists:nodes,id'],
        ];
    }
}
