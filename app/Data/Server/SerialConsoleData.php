<?php

namespace App\Data\Server;

use Spatie\LaravelData\Data;

class SerialConsoleData extends Data
{
    public function __construct(
        /**
         * Whether the VM has a serial device the terminal console can attach to.
         *
         * False is the normal state for a server built before serial devices
         * were provisioned, not a fault -- Proxmox's `termproxy` has nothing to
         * open without one, which is why the terminal console fails while the
         * display console (backed by the VGA device every VM has) works.
         */
        public bool $enabled,
        /**
         * Whether the device is configured but not yet present on the guest.
         *
         * PVE cannot hot-add a serial port, so enabling it on a running server
         * leaves the write pending until the next boot. Distinguished from
         * `$enabled` so the UI can say "restart to finish" instead of offering
         * to enable something that is already configured.
         */
        public bool $restartRequired,
    ) {}
}
