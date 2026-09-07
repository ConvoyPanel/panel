<?php

namespace App\Http\Requests\Admin\Images;

use App\Http\Requests\BaseApiRequest;
use App\Models\ImageDefinition;
use App\Models\Node;
use App\Rules\ValidHardwareProfile;
use Illuminate\Support\Arr;

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

        return $rules;
    }

    private function schemaNode(): ?Node
    {
        return filled($nodeId = $this->input('schema_node_id'))
            ? Node::find($nodeId)
            : null;
    }
}
