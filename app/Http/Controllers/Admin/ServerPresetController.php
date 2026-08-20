<?php

namespace App\Http\Controllers\Admin;

use App\Data\Server\ServerPresetData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Servers\Presets\ServerPresetRequest;
use App\Models\ServerPreset;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\QueryBuilder;

class ServerPresetController
{
    public function index()
    {
        $presets = QueryBuilder::for(ServerPreset::query())
            ->allowedFilters(['name'])
            ->defaultSort('name')
            ->get();

        return ServerPresetData::collect($presets, DataCollection::class);
    }

    public function store(ServerPresetRequest $request)
    {
        $preset = ServerPreset::create($request->attributesForPreset());

        Audit::record(
            AuditEvent::ADMIN_SERVER_PRESET_CREATED,
            subject: $preset,
            properties: ['name' => $preset->name],
        );

        return ServerPresetData::from($preset);
    }

    public function show(ServerPreset $serverPreset)
    {
        return ServerPresetData::from($serverPreset);
    }

    public function update(ServerPresetRequest $request, ServerPreset $serverPreset)
    {
        $serverPreset->update($request->attributesForPreset());

        Audit::record(
            AuditEvent::ADMIN_SERVER_PRESET_UPDATED,
            subject: $serverPreset,
            properties: ['name' => $serverPreset->name, 'changed' => array_keys($serverPreset->getChanges())],
        );

        return ServerPresetData::from($serverPreset);
    }

    public function destroy(ServerPreset $serverPreset): Response
    {
        $name = $serverPreset->name;

        $serverPreset->delete();

        Audit::record(
            AuditEvent::ADMIN_SERVER_PRESET_DELETED,
            subject: $serverPreset,
            properties: ['name' => $name],
        );

        return response()->noContent();
    }
}
