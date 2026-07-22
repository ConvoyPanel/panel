<?php

namespace App\Http\Requests\Admin\Nodes;

use App\Http\Requests\BaseApiRequest;
use App\Models\Node;

class TestNodeConnectionRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $credentialRules = $this->route('node') instanceof Node
            ? 'sometimes|nullable|string|max:191'
            : Node::$validationRules['token_id'];

        return [
            'name' => Node::$validationRules['name'],
            'verify_tls' => 'required|boolean',
            'fqdn' => 'required|string',
            'token_id' => $credentialRules,
            'token_secret' => $credentialRules,
            'port' => Node::$validationRules['port'],
        ];
    }
}
