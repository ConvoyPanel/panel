<?php

namespace App\Http\Requests\Admin\Nodes;

use App\Models\Node;
use App\Rules\Hostname;
use Illuminate\Foundation\Http\FormRequest;

class StoreNodeRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = Node::getRules();

        $rules['fqdn'][] = new Hostname;

        return $rules;
    }
}
