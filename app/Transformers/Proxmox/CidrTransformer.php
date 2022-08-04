<?php

namespace App\Transformers\Proxmox;

use League\Fractal\TransformerAbstract;

class CidrTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];
    
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];
    
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(array $data)
    {
        return [
            'address' => \Arr::get($data, 'address'),
            'cidr' => \Arr::get($data, 'cidr'),
        ];
    }
}
