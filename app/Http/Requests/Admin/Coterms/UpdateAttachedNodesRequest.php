<?php

namespace Convoy\Http\Requests\Admin\Coterms;

use Convoy\Http\Requests\BaseApiRequest;

class UpdateAttachedNodesRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'node_ids' => 'required|array',
            'nodes_ids.*' => 'required|integer|exists:nodes,id',
        ];
    }
}
