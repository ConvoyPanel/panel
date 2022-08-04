<?php

namespace App\Transformers\Application;

use App\Models\Template;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class TemplateTransformer extends TransformerAbstract
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
    public function transform(array $template)
    {
        return [
            'id' => Arr::get($template, 'id'),
            'server_id' => Arr::get($template, 'server_id'),
            'name' => Arr::get($template, 'server')['name'],
        ];
    }
}
