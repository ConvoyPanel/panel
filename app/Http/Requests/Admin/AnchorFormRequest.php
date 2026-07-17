<?php

namespace App\Http\Requests\Admin;

use App\Enums\Anchor\AnchorMode;
use App\Http\Requests\BaseApiRequest;
use App\Models\Anchor;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class AnchorFormRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $anchor = $this->route('anchor');

        return [
            'name' => ['required', 'string', 'max:191'],
            'mode' => ['required', new Enum(AnchorMode::class)],
            'public_url' => ['required', 'url:http,https', 'max:2048'],
            'relay_id' => [
                'nullable',
                'integer',
                Rule::exists('anchors', 'id')->where('mode', AnchorMode::RELAY->value),
                Rule::notIn($anchor instanceof Anchor ? [$anchor->id] : []),
            ],
            'node_ids' => ['sometimes', 'array'],
            'node_ids.*' => ['integer', 'exists:nodes,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->enum('mode', AnchorMode::class) === AnchorMode::RELAY) {
                    if ($this->filled('relay_id')) {
                        $validator->errors()->add('relay_id', 'A relay cannot route through another relay.');
                    }
                    if (count($this->input('node_ids', [])) > 0) {
                        $validator->errors()->add('node_ids', 'Nodes must be assigned to an agent.');
                    }
                }
            },
        ];
    }
}
