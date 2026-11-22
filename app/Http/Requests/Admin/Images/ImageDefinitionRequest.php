<?php

namespace App\Http\Requests\Admin\Images;

use App\Http\Requests\BaseApiRequest;
use App\Models\ImageDefinition;
use App\Models\Node;
use App\Rules\ValidHardwareProfile;
use App\Services\Images\OsProfiles;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class ImageDefinitionRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Arr::only(ImageDefinition::getRules(), [
            'name', 'description', 'is_admin_only', 'ostype', 'minimum_cores', 'minimum_memory',
        ]);

        // Checked against a node's own schema when the form names one, so an
        // admin editing an image for a specific fleet is judged by what that
        // fleet's Proxmox will actually accept. Otherwise the panel's bundled
        // copy applies, which knows less but still catches the obvious.
        $rules['hardware'] = ['sometimes', 'array', new ValidHardwareProfile($this->schemaNode())];

        // In the order they run.
        $rules['provisioner_uuids'] = 'sometimes|array|max:10';
        $rules['provisioner_uuids.*'] = 'string|distinct|exists:provisioners,uuid';

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $current = $this->route('image_definition');
                $ostype = $this->input('ostype')
                    ?? ($current instanceof ImageDefinition ? $current->ostype : null);

                // The scripts reach the guest through cloud-init, and the
                // runner that starts them is a POSIX shell script.
                if (filled($this->input('provisioner_uuids')) && is_string($ostype) && OsProfiles::isWindows($ostype)) {
                    $validator->errors()->add('provisioner_uuids', 'Provisioner scripts run on Linux images only.');
                }
            },
        ];
    }

    /**
     * The definition's own columns, without the provisioners it is attached to.
     *
     * @return array<string, mixed>
     */
    public function attributesForDefinition(): array
    {
        return Arr::except($this->validated(), ['provisioner_uuids']);
    }

    /**
     * The provisioners to attach, in order, or null when the request does not
     * mention them and the current list stays as it is.
     *
     * @return ?list<string>
     */
    public function provisionerUuids(): ?array
    {
        return $this->has('provisioner_uuids')
            ? array_values($this->validated('provisioner_uuids') ?? [])
            : null;
    }

    private function schemaNode(): ?Node
    {
        return filled($nodeId = $this->input('schema_node_id'))
            ? Node::find($nodeId)
            : null;
    }
}
