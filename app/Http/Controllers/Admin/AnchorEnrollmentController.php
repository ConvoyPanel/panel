<?php

namespace App\Http\Controllers\Admin;

use App\Data\Anchor\AnchorEnrollmentQueueData;
use App\Data\Anchor\RelayData;
use App\Data\Node\NodeData;
use App\Data\PaginationMeta;
use App\Enums\Anchor\AnchorMode;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\ApproveAnchorEnrollmentRequest;
use App\Jobs\Node\PollNodeStatusJob;
use App\Models\AnchorEnrollment;
use App\Services\Anchor\AnchorApprovalService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The queue of machines that have introduced themselves.
 *
 * This is what replaced "Add a new node": an operator no longer describes a
 * host to the panel, they confirm a host that already described itself.
 */
class AnchorEnrollmentController
{
    public function __construct(private AnchorApprovalService $approval) {}

    public function index(Request $request)
    {
        $enrollments = QueryBuilder::for(AnchorEnrollment::query())
            ->with('enrollmentKey:id,name')
            ->defaultSort('-id')
            ->allowedFilters(['name', AllowedFilter::exact('mode')])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($enrollments, AnchorEnrollmentQueueData::class);
    }

    public function show(AnchorEnrollment $anchorEnrollment)
    {
        return AnchorEnrollmentQueueData::fromModel(
            $anchorEnrollment->loadMissing('enrollmentKey'),
            $this->approval->suggestions($anchorEnrollment),
        );
    }

    /**
     * Let the machine in, as whatever it enrolled as.
     *
     * The operator supplies only what the host could not know or must not
     * decide: which location it belongs to, how the panel reaches it, and (until
     * the agent mints its own) the Proxmox credentials. Everything else is
     * carried over from what it reported.
     */
    public function approve(ApproveAnchorEnrollmentRequest $request, AnchorEnrollment $anchorEnrollment)
    {
        $name = $anchorEnrollment->name;
        $hostname = $anchorEnrollment->reported('hostname');

        if ($anchorEnrollment->mode === AnchorMode::RELAY) {
            $relay = $this->approval->approveRelay($anchorEnrollment, $request->validated());

            Audit::record(
                AuditEvent::ADMIN_ANCHOR_APPROVED,
                subject: $relay,
                properties: ['name' => $relay->name, 'mode' => 'relay', 'hostname' => $hostname],
            );

            return RelayData::from($relay->loadCount('nodes'));
        }

        $node = $this->approval->approveNode($anchorEnrollment, $request->validated());

        // The scheduled poll would get there within a minute anyway; polling now
        // means the node's status and cluster scope are known while the operator
        // is still looking at the page they approved it from.
        PollNodeStatusJob::dispatch($node->id);

        Audit::record(
            AuditEvent::ADMIN_ANCHOR_APPROVED,
            subject: $node,
            properties: [
                'name' => $node->display_name,
                'mode' => 'agent',
                'enrolled_as' => $name,
                'hostname' => $hostname,
            ],
        );

        return NodeData::from($node->append(['memory_allocated'])->loadCount('servers'));
    }

    /**
     * Turn a machine away.
     *
     * Deleting the row is the whole remediation: the agent's credential stops
     * resolving, so it can neither heartbeat nor open anything. The audit row is
     * what remains, which is the part worth keeping.
     */
    public function destroy(AnchorEnrollment $anchorEnrollment)
    {
        $properties = [
            'name' => $anchorEnrollment->name,
            'mode' => $anchorEnrollment->mode->value,
            'hostname' => $anchorEnrollment->reported('hostname'),
            'source_ip' => $anchorEnrollment->reported('observed_source_ip'),
        ];

        $anchorEnrollment->delete();

        Audit::record(
            AuditEvent::ADMIN_ANCHOR_REJECTED,
            subject: $anchorEnrollment,
            properties: $properties,
        );

        return response()->noContent();
    }
}
