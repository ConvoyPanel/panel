<?php

namespace Convoy\Http\Requests\Admin\Nodes\Isos;

use Convoy\Models\ISO;
use Convoy\Models\Node;
use Illuminate\Validation\Validator;
use Convoy\Http\Requests\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Convoy\Services\Nodes\Isos\IsoService;
use Convoy\Enums\Helpers\ChecksumAlgorithm;

class StoreIsoRequest extends FormRequest
{
    public function rules(): array
    {
        $isoRules = ISO::getRules();

        $rules = [
            'should_download' => 'required|boolean',
            'name' => $isoRules['name'],
            'file_name' => $isoRules['file_name'],
            'hidden' => $isoRules['hidden'],
            'link' => 'required_if:should_download,1|url|max:191|exclude_if:should_download,0',
            'checksum_algorithm' => ['sometimes', new Enum(ChecksumAlgorithm::class), 'exclude_if:should_download,0'],
            'checksum' => 'required_with:checksum_algorithm|string|max:191|exclude_if:should_download,0',
        ];

        return $rules;
    }

    public function withValidator(Validator $validator)
    {

        if (!$this->boolean('should_download')) {
            $validator->after(function (Validator $validator) {
                $node = $this->parameter('node', Node::class);

                $iso = app(IsoService::class)->getIso($node, $this->input('file_name'));

                if (is_null($iso)) {
                    $validator->errors()->add('file_name', 'This ISO doesn\'t exist.');
                }
            });
        }
    }
}
