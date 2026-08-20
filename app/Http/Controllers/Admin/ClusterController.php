<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Controllers\Controller;
use App\Models\Cluster;

class ClusterController extends Controller
{
    /**
     * Clears the identity tripwire (see ClusterIdentityService) after the
     * operator has confirmed which cluster this row really is. While the flag
     * stands, storage adoption and server placement reconciliation for the
     * whole scope hold back, so this is the explicit resolution step. The
     * next poll re-flags if the member sets are still disjoint.
     */
    public function unflag(Cluster $cluster)
    {
        $cluster->forceFill(['flagged_at' => null, 'flag_reason' => null])->save();

        Audit::record(
            AuditEvent::ADMIN_CLUSTER_UNFLAGGED,
            subject: $cluster,
            properties: ['name' => $cluster->name],
        );

        return response()->noContent();
    }
}
