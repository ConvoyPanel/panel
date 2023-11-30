<?php

namespace Convoy\Transformers\Admin;

use Convoy\Models\Coterm;
use League\Fractal\TransformerAbstract;

class CotermTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['token'];

    public function transform(Coterm $coterm): array
    {
        return [
            'id' => (int)$coterm->id,
            'name' => $coterm->name,
            'is_tls_enabled' => (boolean)$coterm->is_tls_enabled,
            'fqdn' => $coterm->fqdn,
            'port' => (int)$coterm->port,
            'nodes_count' => (int)$coterm->nodes_count,
        ];
    }

    public function includeToken(Coterm $coterm): array
    {
        return [
            'token_id' => $coterm->token_id,
            'token' => $coterm->token,
        ];
    }
}
