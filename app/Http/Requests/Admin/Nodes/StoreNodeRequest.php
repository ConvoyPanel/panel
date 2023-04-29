<?php

namespace Convoy\Http\Requests\Admin\Nodes;

use Convoy\Models\Node;
use Convoy\Rules\Fqdn;
use Illuminate\Foundation\Http\FormRequest;

class StoreNodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = Node::getRules();

        $rules['fqdn'][] = Fqdn::make();

        return $rules;
    }
}
