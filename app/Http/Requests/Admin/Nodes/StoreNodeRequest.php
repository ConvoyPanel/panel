<?php

namespace Convoy\Http\Requests\Admin\Nodes;

use Convoy\Models\Node;
use Convoy\Rules\Network\Fqdn;
use Illuminate\Foundation\Http\FormRequest;

class StoreNodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = Node::getRules();

        $rules['hostname'][] = Fqdn::make();

        return $rules;
    }
}
