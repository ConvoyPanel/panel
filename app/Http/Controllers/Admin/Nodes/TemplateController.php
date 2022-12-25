<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\TemplateGroup;
use Convoy\Transformers\Admin\TemplateGroupTransformer;
use Spatie\QueryBuilder\QueryBuilder;

class TemplateController extends ApplicationApiController
{
    public function index()
    {
        $templateGroups = QueryBuilder::for(TemplateGroup::query())
            ->with(['templates' => function ($query) {
                $query->orderBy('order_column');
            }])
            ->allowedFilters(['name'])
            ->get();

        return fractal($templateGroups, new TemplateGroupTransformer)->parseIncludes(['templates'])->respond();
    }
}
