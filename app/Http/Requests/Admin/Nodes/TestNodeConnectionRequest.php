<?php

namespace App\Http\Requests\Admin\Nodes;

use App\Http\Requests\BaseApiRequest;
use App\Models\Node;

class TestNodeConnectionRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => Node::$validationRules['name'],
            'verify_tls' => 'required|boolean',
            'fqdn' => 'required|string',
            'token_id' => Node::$validationRules['token_id'],
            'token_secret' => Node::$validationRules['token_secret'],
            'port' => Node::$validationRules['port'],
        ];
    }
}
