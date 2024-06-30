<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\ApiController;
use Convoy\Http\Requests\Admin\Nodes\TemplateGroups\TemplateGroupRequest;
use Convoy\Http\Requests\Admin\Nodes\TemplateGroups\UpdateGroupOrderRequest;
use Convoy\Models\Node;
use Convoy\Models\TemplateGroup;
use Convoy\Transformers\Admin\TemplateGroupTransformer;
use Spatie\QueryBuilder\QueryBuilder;

class TemplateGroupController extends ApiController
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

        return fractal($templateGroups, new TemplateGroupTransformer())->parseIncludes(
            ['templates'],
        )->respond();
    }

    public function updateOrder(UpdateGroupOrderRequest $request, Node $node)
    {
        TemplateGroup::setNewOrder($request->order);

        return fractal(
            $node->templateGroups()->with('templates')->ordered()->get(),
            new TemplateGroupTransformer(),
        )->parseIncludes(['templates'])->respond();
    }

    public function store(TemplateGroupRequest $request, Node $node)
    {
        $templateGroup = TemplateGroup::create(
            array_merge($request->validated(), [
                'node_id' => $node->id,
            ]),
        );

        return fractal($templateGroup, new TemplateGroupTransformer())->respond();
    }

    public function update(TemplateGroupRequest $request, Node $node, TemplateGroup $templateGroup)
    {
        $templateGroup->update($request->validated());

        return fractal($templateGroup, new TemplateGroupTransformer())->respond();
    }

    public function destroy(Node $node, TemplateGroup $templateGroup)
    {
        $templateGroup->delete();

        return $this->returnNoContent();
    }
}
