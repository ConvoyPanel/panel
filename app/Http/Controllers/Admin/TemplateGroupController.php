<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Requests\Admin\Nodes\TemplateGroups\TemplateGroupRequest;
use App\Models\Node;
use App\Models\TemplateGroup;
use App\Transformers\Admin\TemplateGroupTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class TemplateGroupController
{
    public function index(Request $request): JsonResponse
    {
        $templateGroups = QueryBuilder::for(TemplateGroup::query())
            ->with('templates')
            ->allowedFilters([
                'name',
                AllowedFilter::exact('is_admin_only'),
            ])
            ->defaultSort('name')
            ->get();

        return fractal($templateGroups, new TemplateGroupTransformer)
            ->parseIncludes($request->include)
            ->respond();
    }

    public function store(TemplateGroupRequest $request): JsonResponse
    {
        $templateGroup = TemplateGroup::create($request->validated());

        return fractal($templateGroup, new TemplateGroupTransformer)->respond();
    }

    public function show(TemplateGroup $templateGroup): JsonResponse
    {
        return fractal($templateGroup, new TemplateGroupTransformer)->respond();
    }

    public function update(TemplateGroupRequest $request, TemplateGroup $templateGroup): JsonResponse
    {
        $templateGroup->update($request->validated());

        return fractal($templateGroup, new TemplateGroupTransformer)->respond();
    }

    public function destroy(TemplateGroup $templateGroup): Response
    {
        $templateGroup->delete();

        return response()->noContent();
    }
}
