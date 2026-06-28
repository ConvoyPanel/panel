<?php

namespace App\Http\Controllers\Admin;

use App\Data\Template\TemplateGroupData;
use App\Http\Requests\Admin\Nodes\TemplateGroups\TemplateGroupRequest;
use App\Models\TemplateGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TemplateGroupController
{
    public function index(Request $request)
    {
        $templateGroups = QueryBuilder::for(TemplateGroup::query())
            ->with('templates')
            ->allowedFilters([
                'name',
                AllowedFilter::exact('is_admin_only'),
            ])
            ->defaultSort('name')
            ->get();

        return TemplateGroupData::collect($templateGroups, DataCollection::class)
            ->include(...((array) $request->query('include', [])));
    }

    public function store(TemplateGroupRequest $request)
    {
        $templateGroup = TemplateGroup::create($request->validated());

        return TemplateGroupData::from($templateGroup);
    }

    public function show(TemplateGroup $templateGroup)
    {
        return TemplateGroupData::from($templateGroup);
    }

    public function update(TemplateGroupRequest $request, TemplateGroup $templateGroup)
    {
        $templateGroup->update($request->validated());

        return TemplateGroupData::from($templateGroup);
    }

    public function destroy(TemplateGroup $templateGroup): Response
    {
        $templateGroup->delete();

        return response()->noContent();
    }
}
