<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Nodes\Templates\UpdateGroupOrderRequest;
use Convoy\Models\Node;
use Convoy\Models\TemplateGroup;
use Convoy\Transformers\Admin\TemplateGroupTransformer;
use Spatie\QueryBuilder\QueryBuilder;

class TemplateController extends ApplicationApiController
{
    public function index(Node $node)
    {
        $templateGroups = QueryBuilder::for(TemplateGroup::query())
            ->where('template_groups.node_id', $node->id)
            ->defaultSort('order_column')
            ->with(['templates' => function ($query) {
                $query->orderBy('order_column');
            }])
            ->allowedFilters(['name'])
            ->get();

        return fractal($templateGroups, new TemplateGroupTransformer)->parseIncludes(['templates'])->respond();
    }

    public function updateGroupOrder(UpdateGroupOrderRequest $request, Node $node)
    {
        TemplateGroup::setNewOrder($request->order);

        return fractal($node->templateGroups()->with('templates')->ordered()->get(), new TemplateGroupTransformer)->parseIncludes(['templates'])->respond();
    }
}
