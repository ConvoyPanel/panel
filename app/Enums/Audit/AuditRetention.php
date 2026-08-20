<?php

namespace App\Enums\Audit;

/**
 * How long an audit entry is kept. Security-relevant events are low volume and are the entire
 * reason an audit trail exists, so they are never pruned; high-churn operational events age out
 * on the configured window. See {@see AuditEvent::retention()}.
 */
enum AuditRetention: string
{
    /** Deleted once older than `audit.prune_days`. */
    case STANDARD = 'standard';

    /** Never deleted by the pruner. */
    case FOREVER = 'forever';
}
