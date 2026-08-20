<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\Audit\AuditLogData;
use App\Data\PaginationMeta;
use App\Models\Server;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The server's activity feed, as shown to whoever can reach the server.
 *
 * Scoped two ways: to this server as the subject, and — for non-admins — to the events the catalog
 * marks client-visible. Actions staff took on the server do appear here; whether the individual
 * admin is named is governed by AuditSettings::$reveal_staff_identity, applied in AuditActorData.
 */
class AuditLogController
{
    public function __invoke(Request $request, Server $server)
    {
        $query = QueryBuilder::for($server->auditLogs())
            // Eager loaded so a page of entries does not issue a query per row for the actor and
            // the thing acted on.
            ->with(['actor', 'subject'])
            ->allowedFilters(['event', 'batch'])
            ->defaultSort('-created_at')
            ->allowedSorts(['created_at']);

        if (! $request->user()?->root_admin) {
            $query->clientVisible();
        }

        $logs = $query
            ->paginate(min($request->integer('per_page', 25), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($logs, AuditLogData::class);
    }
}
