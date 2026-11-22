<?php

namespace App\Http\Requests\Admin\Provisioners;

use App\Http\Requests\BaseApiRequest;
use App\Models\Provisioner;

class ProvisionerRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = $this->method() === 'PUT'
            ? Provisioner::getRulesForUpdate($this->parameter('provisioner', Provisioner::class))
            : Provisioner::getRules();

        return [
            'name' => $rules['name'],
            'description' => $rules['description'],
            'script' => $rules['script'],
            'fields' => [...$rules['fields'], 'max:'.Provisioner::MAX_FIELDS],
            'fields.*' => 'array:key,label,required',
            'fields.*.key' => ['required', 'string', 'distinct', 'regex:'.Provisioner::FIELD_KEY_PATTERN],
            'fields.*.label' => 'required|string|between:1,40',
            'fields.*.required' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'fields.*.key.regex' => 'A field key starts with a lowercase letter and holds only lowercase letters, digits and underscores, up to 32 characters.',
            'fields.*.key.distinct' => 'Two fields have the same key.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForProvisioner(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'script' => $validated['script'],
            'fields' => collect($validated['fields'])
                ->map(fn (array $field) => [
                    'key' => $field['key'],
                    'label' => $field['label'],
                    'required' => (bool) $field['required'],
                ])
                ->values()
                ->all(),
        ];
    }
}
