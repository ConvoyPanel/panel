<?php

namespace Convoy\Transformers\Application;

use League\Fractal\TransformerAbstract;
use Illuminate\Support\Arr;

class SpecificationTransformer extends TransformerAbstract
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
            'node' => Arr::get($data, 'node'),
            'cores' => Arr::get($data, 'cores'),
            'memory' => Arr::get($data, 'memory'),
            'disk' => Arr::get($data, 'maxmem'),
            'disks' => Arr::get($data, 'disks'),
            'ipconfig' => Arr::get($data, 'ipconfig'),
        ];
    }

    public function getResourceName(): string
    {
        return 'server_specifications';
    }
}
