<?php

namespace Convoy\Http\Requests\Admin\Nodes;

use Convoy\Models\Node;
use Convoy\Rules\Fqdn;
use Illuminate\Foundation\Http\FormRequest;

class StoreNodeRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = Node::getRules();

        $rules['fqdn'][] = Fqdn::make();

        return $rules;
    }
}
