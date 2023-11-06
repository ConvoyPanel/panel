<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Models\Node;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Spatie\QueryBuilder\QueryBuilder;
use Convoy\Transformers\Admin\TemplateTransformer;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Nodes\Templates\TemplateRequest;
use Convoy\Http\Requests\Admin\Nodes\Templates\UpdateTemplateOrderRequest;

class TemplateController extends ApplicationApiController
{
    public function index(Node $node, TemplateGroup $templateGroup)
    {
        $templates = QueryBuilder::for(Template::query())
            ->where('templates.template_group_id', $templateGroup->id)
            ->defaultSort('order_column')
            ->get();

        return fractal($templates, new TemplateTransformer)->respond();
    }

    public function store(TemplateRequest $request, Node $node, TemplateGroup $templateGroup)
    {
        $template = Template::create(array_merge($request->validated(), [
            'node_id' => $node->id,
            'template_group_id' => $templateGroup->id,
        ]));

        return fractal($template, new TemplateTransformer)->respond();
    }

    public function update(TemplateRequest $request, Node $node, TemplateGroup $templateGroup, Template $template)
    {
        $template->update($request->validated());

        return fractal($template, new TemplateTransformer)->respond();
    }

    public function destroy(Node $node, TemplateGroup $templateGroup, Template $template)
    {
        $template->delete();

        return $this->returnNoContent();
    }

    public function updateOrder(UpdateTemplateOrderRequest $request, Node $node, TemplateGroup $templateGroup)
    {
        Template::setNewOrder($request->order);

        return fractal($templateGroup->templates()->ordered()->get(), new TemplateTransformer)->respond();
    }
}
