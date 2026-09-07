<?php

namespace App\Http\Requests\Admin\ISOs;

use App\Http\Requests\BaseApiRequest;
use App\Models\ISO;
use Illuminate\Validation\Validator;

class StoreISORequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = ISO::getRules();

        return [
            'name' => $rules['name'],
            'file_name' => $rules['file_name'],
            'hidden' => $rules['hidden'],

            // A link the operator hosts, or a file they uploaded. Exactly one:
            // both answer the same question, so accepting both would only leave
            // a question about which one a node was given.
            'url' => ['nullable', 'url', 'max:2048', 'required_without:path', 'prohibits:path'],
            'path' => ['nullable', 'string', 'max:191', 'required_without:url', 'prohibits:url'],

            'sha256' => ['nullable', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/i'],
            'size' => 'sometimes|numeric|min:0',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // The file name is what the ISO is called on every node it ever
                // lands on, so two library entries sharing one would fight over
                // the same file.
                if (ISO::where('file_name', $this->string('file_name'))->exists()) {
                    $validator->errors()->add(
                        'file_name',
                        __('validation.unique', ['attribute' => 'file name']),
                    );
                }
            },
        ];
    }
}
