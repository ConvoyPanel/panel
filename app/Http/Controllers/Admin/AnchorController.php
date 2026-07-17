<?php

namespace App\Http\Controllers\Admin;

use App\Data\Anchor\AnchorData;
use App\Data\PaginationMeta;
use App\Enums\Anchor\AnchorMode;
use App\Http\Requests\Admin\AnchorFormRequest;
use App\Models\Anchor;
use App\Models\Node;
use App\Services\Anchor\AnchorEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AnchorController
{
    public function index(Request $request)
    {
        $anchors = QueryBuilder::for(Anchor::query())
            ->withCount(['nodes', 'agents'])
            ->defaultSort('name')
            ->allowedFilters(['name', AllowedFilter::exact('mode')])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($anchors, AnchorData::class);
    }

    public function show(Anchor $anchor)
    {
        return AnchorData::from($anchor->loadCount(['nodes', 'agents']));
    }

    public function store(AnchorFormRequest $request)
    {
        $anchor = Anchor::create([
            ...Arr::except($request->validated(), ['node_ids']),
            'uuid' => (string) Str::uuid(),
            'secret' => Str::random(64),
        ]);
        $this->syncNodes($anchor, $request->input('node_ids', []));

        return AnchorData::from($anchor->loadCount(['nodes', 'agents']));
    }

    public function update(AnchorFormRequest $request, Anchor $anchor)
    {
        $anchor->update(Arr::except($request->validated(), ['node_ids']));
        if ($request->has('node_ids')) {
            $this->syncNodes($anchor, $request->input('node_ids', []));
        }

        return AnchorData::from($anchor->loadCount(['nodes', 'agents']));
    }

    public function enrollment(Anchor $anchor, AnchorEnrollmentService $enrollment)
    {
        return $enrollment->issue($anchor);
    }

    public function destroy(Anchor $anchor)
    {
        $anchor->loadCount(['nodes', 'agents']);
        if ($anchor->nodes_count > 0 || $anchor->agents_count > 0) {
            throw new BadRequestHttpException('Detach this Anchor before deleting it.');
        }

        $anchor->delete();

        return response()->noContent();
    }

    /** @param array<int, int> $nodeIds */
    private function syncNodes(Anchor $anchor, array $nodeIds): void
    {
        if ($anchor->mode !== AnchorMode::AGENT) {
            return;
        }

        Node::whereIn('id', $nodeIds)->update(['anchor_id' => $anchor->id]);
        Node::where('anchor_id', $anchor->id)->whereNotIn('id', $nodeIds)->update(['anchor_id' => null]);
    }
}
