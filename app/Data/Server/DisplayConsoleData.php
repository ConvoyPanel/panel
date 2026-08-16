<?php

namespace App\Data\Server;

use Spatie\LaravelData\Data;

class DisplayConsoleData extends Data
{
    public function __construct(
        /**
         * Whether the running guest has a VNC display to connect to.
         *
         * False is a deliberate configuration rather than a fault: a VM whose
         * display is a serial terminal is started with `-nographic`, so QEMU
         * has no VNC server and `qm vncproxy` fails on the QMP `set_password`
         * with "No VNC display is present".
         */
        public bool $enabled,
        /**
         * Whether the display is configured but not yet attached to the guest.
         *
         * PVE cannot change a running VM's display, so the write lands in the
         * pending set and takes effect at next boot. Distinguished from
         * `$enabled` so the UI can say "restart to finish" instead of offering
         * to enable something that is already configured.
         */
        public bool $restartRequired,
        /**
         * The display the running guest actually has (`serial0`, `none`), or
         * null where PVE's default applies. Named so the UI can say which
         * setting is in the way rather than that something unspecified is.
         */
        public ?string $display,
    ) {}
}
