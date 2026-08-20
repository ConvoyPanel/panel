<?php

namespace App\Http\Controllers\Admin;

use App\Data\Audit\AuditLogData;
use App\Data\PaginationMeta;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The panel-wide audit feed. Unfiltered by visibility — this route is already admin-only, and the
 * client-visibility flag exists to keep things out of *customer* views, not out of this one.
 */
class AuditLogController
{
    public function __invoke(Request $request)
    {
        $logs = QueryBuilder::for(AuditLog::query())
            ->with(['actor', 'subject'])
            ->allowedFilters([
                'event',
                'batch',
                AllowedFilter::exact('actor_id'),
                AllowedFilter::exact('subject_id'),
                AllowedFilter::exact('subject_type'),
                // Prefix match on the dotted event key, so "filter[area]=admin.node" pulls back
                // every node event without the caller naming each one.
                AllowedFilter::callback('area', fn ($query, $value) => $query->where(
                    // Wildcards stripped from the input so a caller cannot turn the prefix match
                    // into an arbitrary LIKE pattern.
                    'event', 'like', str_replace(['%', '_'], '', (string) $value).'%',
                )),
                AllowedFilter::callback('since', fn ($query, $value) => $query->where('created_at', '>=', $value)),
                AllowedFilter::callback('until', fn ($query, $value) => $query->where('created_at', '<=', $value)),
            ])
            ->defaultSort('-created_at')
            ->allowedSorts(['created_at'])
            ->paginate(min($request->integer('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($logs, AuditLogData::class);
    }
}
