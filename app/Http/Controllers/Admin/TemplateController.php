<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Requests\Admin\Nodes\Templates\TemplateRequest;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Transformers\Admin\TemplateTransformer;
use Spatie\QueryBuilder\QueryBuilder;

class TemplateController
{
    public function index(TemplateGroup $templateGroup): JsonResponse
    {
        $templates = QueryBuilder::for($templateGroup->templates())
            ->allowedFilters([
                'name',
                AllowedFilter::exact('is_admin_only')
            ])
            ->get();

        return fractal($templates, new TemplateTransformer)->respond();
    }

    public function store(
        TemplateRequest $request,
        TemplateGroup $templateGroup
    ): JsonResponse
    {
        $template = $templateGroup->templates()->create($request->validated());

        return fractal($template, new TemplateTransformer)->respond();
    }

    public function show(TemplateGroup $templateGroup, Template $template): JsonResponse
    {
        return fractal($template, new TemplateTransformer)->respond();
    }

    public function update(
        TemplateRequest $request,
        TemplateGroup $templateGroup,
        Template $template,
    ): JsonResponse {
        $template->update($request->validated());

        return fractal($template, new TemplateTransformer)->respond();
    }

    public function destroy(TemplateGroup $templateGroup, Template $template): Response
    {
        $template->delete();

        return response()->noContent();
    }
}
