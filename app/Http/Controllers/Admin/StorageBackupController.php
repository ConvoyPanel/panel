<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\Backups\BackupDeletionService;
use Illuminate\Validation\ValidationException;

/**
 * Deleting a backup as an administrator, from the storage it occupies.
 *
 * The only route that could delete a backup was the client one, scoped to a
 * server -- fine for the owner of that server, useless for an operator looking at
 * a full disk who does not want to work out which server each backup came from
 * first.
 *
 * Goes through the same `BackupDeletionService` as the client route, so the file
 * is removed from Proxmox rather than only the row.
 */
class StorageBackupController extends Controller
{
    public function __construct(private BackupDeletionService $deletion) {}

    public function destroy(Backup $backup)
    {
        // Locked backups are locked deliberately. Refusing here with a sentence
        // is the same answer the row already shows, in the place that enforces
        // it.
        if ($backup->is_locked) {
            throw ValidationException::withMessages([
                'backup' => 'That backup is locked. Unlock it before deleting it.',
            ]);
        }

        $properties = ['backup' => $backup->name, 'backup_uuid' => $backup->uuid];
        $server = $backup->server;

        $this->deletion->handle($backup);

        // Subject is the server, matching the client-side backup events, so an owner sees staff
        // deleting their backup in the same feed as their own backup activity.
        Audit::record(AuditEvent::ADMIN_BACKUP_DELETED, subject: $server, properties: $properties);

        return response()->noContent();
    }
}
