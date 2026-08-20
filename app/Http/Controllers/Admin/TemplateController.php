<?php

namespace App\Http\Controllers\Admin;

use App\Data\Server\Templates\TemplateData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Nodes\Templates\TemplateRequest;
use App\Models\Template;
use App\Models\TemplateGroup;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TemplateController
{
    public function index(TemplateGroup $templateGroup)
    {
        $templates = QueryBuilder::for($templateGroup->templates())
            ->allowedFilters([
                'name',
                AllowedFilter::exact('is_admin_only'),
            ])
            ->defaultSort('name')
            ->get();

        return TemplateData::collect($templates, DataCollection::class);
    }

    public function store(TemplateRequest $request, TemplateGroup $templateGroup)
    {
        $template = $templateGroup->templates()->create($request->validated());

        Audit::record(
            AuditEvent::ADMIN_TEMPLATE_CREATED,
            subject: $template,
            properties: ['name' => $template->name, 'group' => $templateGroup->name],
        );

        return TemplateData::from($template);
    }

    public function show(TemplateGroup $templateGroup, Template $template)
    {
        return TemplateData::from($template);
    }

    public function update(TemplateRequest $request, TemplateGroup $templateGroup, Template $template)
    {
        $template->update($request->validated());

        Audit::record(
            AuditEvent::ADMIN_TEMPLATE_UPDATED,
            subject: $template,
            properties: ['name' => $template->name, 'changed' => array_keys($template->getChanges())],
        );

        return TemplateData::from($template);
    }

    public function destroy(TemplateGroup $templateGroup, Template $template): Response
    {
        $name = $template->name;

        $template->delete();

        Audit::record(
            AuditEvent::ADMIN_TEMPLATE_DELETED,
            subject: $template,
            properties: ['name' => $name, 'group' => $templateGroup->name],
        );

        return response()->noContent();
    }
}
