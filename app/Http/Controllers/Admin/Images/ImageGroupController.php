<?php

namespace App\Http\Controllers\Admin\Images;

use App\Data\Image\ImageGroupData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Images\ImageGroupRequest;
use App\Models\ImageGroup;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ImageGroupController
{
    public function index()
    {
        $groups = QueryBuilder::for(ImageGroup::query())
            ->allowedFilters(['name', AllowedFilter::exact('is_admin_only')])
            ->allowedIncludes(['definitions'])
            ->defaultSort('name')
            ->get();

        return ImageGroupData::collect($groups, DataCollection::class);
    }

    public function store(ImageGroupRequest $request)
    {
        $group = ImageGroup::create($request->validated());

        Audit::record(
            AuditEvent::ADMIN_IMAGE_GROUP_CREATED,
            subject: $group,
            properties: ['name' => $group->name],
        );

        return ImageGroupData::from($group);
    }

    public function show(ImageGroup $imageGroup)
    {
        return ImageGroupData::from($imageGroup->load('definitions.versions'))->include('definitions');
    }

    public function update(ImageGroupRequest $request, ImageGroup $imageGroup)
    {
        $imageGroup->update($request->validated());

        Audit::record(
            AuditEvent::ADMIN_IMAGE_GROUP_UPDATED,
            subject: $imageGroup,
            properties: ['name' => $imageGroup->name, 'changed' => array_keys($imageGroup->getChanges())],
        );

        return ImageGroupData::from($imageGroup);
    }

    public function destroy(ImageGroup $imageGroup): Response
    {
        $name = $imageGroup->name;

        $imageGroup->delete();

        Audit::record(
            AuditEvent::ADMIN_IMAGE_GROUP_DELETED,
            subject: $imageGroup,
            properties: ['name' => $name],
        );

        return response()->noContent();
    }
}
