<?php

namespace App\Http\Controllers\Admin\Images;

use App\Data\Image\ImageDefinitionData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Images\ImageDefinitionRequest;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\Provisioner;
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
            ->with(['versions', 'provisioners'])
            ->get();

        return ImageDefinitionData::collect($definitions, DataCollection::class);
    }

    public function store(ImageDefinitionRequest $request, ImageGroup $imageGroup)
    {
        $definition = $imageGroup->definitions()->create($request->attributesForDefinition());

        $this->syncProvisioners($definition, $request->provisionerUuids());

        Audit::record(
            AuditEvent::ADMIN_IMAGE_CREATED,
            subject: $definition,
            properties: ['name' => $definition->name, 'group' => $imageGroup->name],
        );

        return ImageDefinitionData::from($definition->load('provisioners'));
    }

    public function show(ImageGroup $imageGroup, ImageDefinition $imageDefinition)
    {
        return ImageDefinitionData::from($imageDefinition->load(['versions', 'provisioners']))->include('versions');
    }

    public function update(
        ImageDefinitionRequest $request,
        ImageGroup $imageGroup,
        ImageDefinition $imageDefinition,
    ) {
        $imageDefinition->update($request->attributesForDefinition());

        $provisionersChanged = $this->syncProvisioners($imageDefinition, $request->provisionerUuids());

        Audit::record(
            AuditEvent::ADMIN_IMAGE_UPDATED,
            subject: $imageDefinition,
            properties: [
                'name' => $imageDefinition->name,
                'changed' => [
                    ...$imageDefinition->getChangedAttributeNames(),
                    ...($provisionersChanged ? ['provisioners'] : []),
                ],
            ],
        );

        return ImageDefinitionData::from($imageDefinition->load('provisioners'));
    }

    /**
     * Attach exactly these provisioners, in this order.
     *
     * @param  ?list<string>  $uuids  null leaves the current list alone
     * @return bool whether anything changed
     */
    private function syncProvisioners(ImageDefinition $definition, ?array $uuids): bool
    {
        if ($uuids === null) {
            return false;
        }

        $before = $definition->provisioners()->pluck('provisioners.uuid')->all();

        $ids = Provisioner::whereIn('uuid', $uuids)->pluck('id', 'uuid');

        $definition->provisioners()->sync(
            collect($uuids)
                ->values()
                ->mapWithKeys(fn (string $uuid, int $position) => [$ids[$uuid] => ['position' => $position]])
                ->all(),
        );

        return $before !== $uuids;
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
