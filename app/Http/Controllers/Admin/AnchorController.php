<?php

namespace App\Http\Controllers\Admin;

use App\Data\Anchor\AnchorData;
use App\Data\PaginationMeta;
use App\Enums\Anchor\AnchorMode;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
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
            ->with('relay:id,name')
            ->withCount(['nodes', 'agents'])
            ->defaultSort('name')
            ->allowedFilters(['name', AllowedFilter::exact('mode')])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($anchors, AnchorData::class);
    }

    public function show(Anchor $anchor)
    {
        // The detail screen names the nodes rather than counting them: what
        // loses console access if this anchor goes away is the question the
        // screen exists to answer.
        return AnchorData::from(
            $this->hydrate($anchor)->load([
                'nodes' => fn ($query) => $query->withCount('servers')->orderBy('display_name'),
            ])
        );
    }

    public function store(AnchorFormRequest $request)
    {
        $anchor = Anchor::create([
            ...Arr::except($request->validated(), ['node_ids']),
            'uuid' => (string) Str::uuid(),
            'secret' => Str::random(64),
        ]);
        $this->syncNodes($anchor, $request->input('node_ids', []));

        // The generated secret is never recorded — it is a live credential for the agent.
        Audit::record(
            AuditEvent::ADMIN_ANCHOR_CREATED,
            subject: $anchor,
            properties: ['name' => $anchor->name],
        );

        return AnchorData::from($this->hydrate($anchor));
    }

    public function update(AnchorFormRequest $request, Anchor $anchor)
    {
        $anchor->update(Arr::except($request->validated(), ['node_ids']));
        if ($request->has('node_ids')) {
            $this->syncNodes($anchor, $request->input('node_ids', []));
        }

        Audit::record(
            AuditEvent::ADMIN_ANCHOR_UPDATED,
            subject: $anchor,
            properties: ['name' => $anchor->name, 'changed' => array_keys($anchor->getChanges())],
        );

        return AnchorData::from($this->hydrate($anchor));
    }

    public function enrollment(Anchor $anchor, AnchorEnrollmentService $enrollment)
    {
        $enrollmentDetails = $enrollment->issue($anchor);

        // Enrollment rotates the installation secret, so this both grants access and revokes the
        // previous one. The issued secret itself is never recorded.
        Audit::record(
            AuditEvent::ADMIN_ANCHOR_ENROLLMENT_ROTATED,
            subject: $anchor,
            properties: ['name' => $anchor->name],
        );

        return $enrollmentDetails;
    }

    public function destroy(Anchor $anchor)
    {
        $anchor->loadCount(['nodes', 'agents']);
        if ($anchor->nodes_count > 0 || $anchor->agents_count > 0) {
            throw new BadRequestHttpException('Detach this Anchor before deleting it.');
        }

        $name = $anchor->name;

        $anchor->delete();

        Audit::record(
            AuditEvent::ADMIN_ANCHOR_DELETED,
            subject: $anchor,
            properties: ['name' => $name],
        );

        return response()->noContent();
    }

    /**
     * Everything AnchorData reads beyond the row itself: the counts the roster
     * shows as load, and the relay it names as the route.
     */
    private function hydrate(Anchor $anchor): Anchor
    {
        return $anchor->loadCount(['nodes', 'agents'])->load('relay:id,name');
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
