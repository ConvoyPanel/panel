<?php

namespace App\Http\Controllers\Admin;

use App\Data\Server\ServerPresetData;
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

        return ServerPresetData::from($preset);
    }

    public function show(ServerPreset $serverPreset)
    {
        return ServerPresetData::from($serverPreset);
    }

    public function update(ServerPresetRequest $request, ServerPreset $serverPreset)
    {
        $serverPreset->update($request->attributesForPreset());

        return ServerPresetData::from($serverPreset);
    }

    public function destroy(ServerPreset $serverPreset): Response
    {
        $serverPreset->delete();

        return response()->noContent();
    }
}
