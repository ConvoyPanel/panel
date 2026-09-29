<?php

namespace App\Enums\Image;

/**
 * What the panel already holds of a catalogue entry.
 *
 * Computed per fetch by comparing the catalogue's disk hashes against the
 * versions already imported, so nothing has to be stored or kept in step.
 */
enum RegistryImportStatus: string
{
    /** Nothing in the panel was imported from this entry. */
    case NEW = 'new';

    /** The panel holds a version with exactly these disks. */
    case IMPORTED = 'imported';

    /** The entry was imported, and has been rebuilt since. */
    case UPDATE_AVAILABLE = 'update_available';
}
