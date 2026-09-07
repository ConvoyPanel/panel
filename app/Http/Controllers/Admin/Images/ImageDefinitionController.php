<?php

namespace App\Http\Controllers\Admin\Images;

use App\Data\Image\ImageDefinitionData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Images\ImageDefinitionRequest;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ImageDefinitionController
{
    public function index(ImageGroup $imageGroup)
    {
        $definitions = QueryBuilder::for($imageGroup->definitions())
            ->allowedFilters(['name', AllowedFilter::exact('is_admin_only')])
            ->allowedIncludes(['versions'])
            ->defaultSort('name')
            ->with('versions')
            ->get();

        return ImageDefinitionData::collect($definitions, DataCollection::class);
    }

    public function store(ImageDefinitionRequest $request, ImageGroup $imageGroup)
    {
        $definition = $imageGroup->definitions()->create($request->validated());

        Audit::record(
            AuditEvent::ADMIN_IMAGE_CREATED,
            subject: $definition,
            properties: ['name' => $definition->name, 'group' => $imageGroup->name],
        );

        return ImageDefinitionData::from($definition);
    }

    public function show(ImageGroup $imageGroup, ImageDefinition $imageDefinition)
    {
        return ImageDefinitionData::from($imageDefinition->load('versions'))->include('versions');
    }

    public function update(
        ImageDefinitionRequest $request,
        ImageGroup $imageGroup,
        ImageDefinition $imageDefinition,
    ) {
        $imageDefinition->update($request->validated());

        Audit::record(
            AuditEvent::ADMIN_IMAGE_UPDATED,
            subject: $imageDefinition,
            properties: [
                'name' => $imageDefinition->name,
                'changed' => array_keys($imageDefinition->getChanges()),
            ],
        );

        return ImageDefinitionData::from($imageDefinition);
    }

    public function destroy(ImageGroup $imageGroup, ImageDefinition $imageDefinition): Response
    {
        $name = $imageDefinition->name;

        $imageDefinition->delete();

        Audit::record(
            AuditEvent::ADMIN_IMAGE_DELETED,
            subject: $imageDefinition,
            properties: ['name' => $name, 'group' => $imageGroup->name],
        );

        return response()->noContent();
    }
}
