<?php

namespace App\Http\Controllers\Admin;

use App\Data\Provisioner\ProvisionerData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Provisioners\ProvisionerRequest;
use App\Models\Provisioner;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\QueryBuilder;

class ProvisionerController
{
    public function index()
    {
        $provisioners = QueryBuilder::for(Provisioner::query()->withCount('images'))
            ->allowedFilters(['name'])
            ->defaultSort('name')
            ->get();

        return ProvisionerData::collect($provisioners, DataCollection::class);
    }

    public function store(ProvisionerRequest $request)
    {
        $provisioner = Provisioner::create($request->attributesForProvisioner());

        Audit::record(
            AuditEvent::ADMIN_PROVISIONER_CREATED,
            subject: $provisioner,
            properties: ['name' => $provisioner->name],
        );

        return ProvisionerData::from($provisioner->loadCount('images'));
    }

    public function show(Provisioner $provisioner)
    {
        return ProvisionerData::from($provisioner->loadCount('images'));
    }

    /**
     * An edit reaches servers the next time one is built or reinstalled from
     * an image that runs it. Snippets already on a node keep the script they
     * were written with.
     */
    public function update(ProvisionerRequest $request, Provisioner $provisioner)
    {
        $provisioner->update($request->attributesForProvisioner());

        Audit::record(
            AuditEvent::ADMIN_PROVISIONER_UPDATED,
            subject: $provisioner,
            properties: ['name' => $provisioner->name, 'changed' => $provisioner->getChangedAttributeNames()],
        );

        return ProvisionerData::from($provisioner->loadCount('images'));
    }

    /** Detaches it from every image. Servers waiting on it keep their snippet. */
    public function destroy(Provisioner $provisioner): Response
    {
        $name = $provisioner->name;

        $provisioner->delete();

        Audit::record(
            AuditEvent::ADMIN_PROVISIONER_DELETED,
            subject: $provisioner,
            properties: ['name' => $name],
        );

        return response()->noContent();
    }
}
