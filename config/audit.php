<?php

return [
    /*
     * Days before an audit entry is deleted. Events marked AuditRetention::FOREVER in the
     * AuditEvent catalog — authentication, credential changes, token and user lifecycle — ignore
     * this and are never pruned.
     *
     * Set to null to disable pruning entirely, which also unregisters the scheduled command.
     */
    'prune_days' => env('APP_AUDIT_PRUNE_DAYS', 90),

    /*
     * Rows deleted per statement by the pruner, so a long-neglected install does not issue one
     * enormous DELETE.
     */
    'prune_chunk' => env('APP_AUDIT_PRUNE_CHUNK', 1000),
];
