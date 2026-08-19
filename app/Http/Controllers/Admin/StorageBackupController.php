<?php

namespace App\Http\Controllers\Admin;

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

        $this->deletion->handle($backup);

        return response()->noContent();
    }
}
