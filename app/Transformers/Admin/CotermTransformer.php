<?php

namespace App\Transformers\Admin;

use App\Models\Coterm;
use League\Fractal\TransformerAbstract;

class CotermTransformer extends TransformerAbstract
{
    public function __construct(private bool $includeToken = false)
    {
    }

    public function transform(Coterm $coterm): array
    {
        $transformed = [
            'id' => (int)$coterm->id,
            'name' => $coterm->name,
            'is_tls_enabled' => (boolean)$coterm->is_tls_enabled,
            'fqdn' => $coterm->fqdn,
            'port' => (int)$coterm->port,
            'nodes_count' => (int)$coterm->nodes_count,
        ];

        if ($this->includeToken) {
            $transformed['token_id'] = $coterm->token_id;
            $transformed['token'] = $coterm->token;
        }

        return $transformed;
    }
}
