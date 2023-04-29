<?php

namespace Convoy\Http\Requests\Admin\Nodes\Isos;

use Convoy\Enums\Helpers\ChecksumAlgorithm;
use Convoy\Models\ISO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreIsoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isoRules = ISO::getRules();

        $rules = [
            'name' => $isoRules['name'],
            'file_name' => $isoRules['file_name'],
            'hidden' => $isoRules['hidden'],
            'link' => 'required|url',
            'checksum_algorithm' => ['sometimes', new Enum(ChecksumAlgorithm::class)],
            'checksum' => 'required_with:checksum_algorithm|string|max:191',
        ];

        return $rules;
    }
}
