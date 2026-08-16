<?php

namespace App\Services\Servers;

use App\Data\Server\DisplayConsoleData;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxConfigClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;

/**
 * The display the graphical console attaches to.
 *
 * A VM whose `vga` is a serial terminal (`serial0`) or switched off (`none`) is
 * started with `-nographic`: QEMU never opens a VNC server for it, and `qm
 * vncproxy` fails on the QMP `set_password` with "No VNC display is present".
 * That is the display console's counterpart to a missing serial device, so it
 * is handled the same way -- read it before offering the console, and offer to
 * change it rather than leaving someone to guess at a Proxmox error.
 *
 * `vga: serial0` is the standard recipe for cloud images (Proxmox's own docs
 * pair it with `serial0: socket`), so a server built from one arrives with the
 * display console unusable and nothing saying why.
 *
 * @see SerialConsoleService for the same problem from the other console's side.
 */
readonly class DisplayConsoleService
{
    private const DEVICE = 'vga';

    /**
     * PVE's default display, and the least opinionated thing to switch back to:
     * it is what a VM created without a `vga` key already gets.
     */
    private const DISPLAY = 'std';

    public function __construct(private ProxmoxConfigClient $configClient) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function status(Server $server): DisplayConsoleData
    {
        $rows = $this->configClient->setServer($server)->getPendingConfig();

        foreach ($rows as $row) {
            if (($row['key'] ?? null) !== self::DEVICE) {
                continue;
            }

            // `value` is what the running guest has; `pending` is what it will
            // have at next boot. A row with only the latter is the mid-state
            // between changing the display and restarting.
            $live = (string) ($row['value'] ?? '');
            $queued = (string) ($row['pending'] ?? '');
            $enabled = $this->hasDisplay($live);

            return new DisplayConsoleData(
                enabled: $enabled,
                restartRequired: ! $enabled && $queued !== '' && $this->hasDisplay($queued),
                display: $live === '' ? null : $live,
            );
        }

        // No `vga` key at all means PVE's default, which has a display.
        return new DisplayConsoleData(enabled: true, restartRequired: false, display: null);
    }

    /**
     * Give the VM a graphical display, unless it already has one.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function enable(Server $server): DisplayConsoleData
    {
        $status = $this->status($server);

        if ($status->enabled || $status->restartRequired) {
            return $status;
        }

        $this->configClient->setServer($server)->update([
            self::DEVICE => self::DISPLAY,
        ]);

        return $this->status($server);
    }

    /**
     * Whether a `vga` value leaves QEMU with a VNC server.
     *
     * The value carries options (`std,memory=32`), and an empty one is the
     * default rather than an absence, so only the type is judged.
     */
    private function hasDisplay(string $value): bool
    {
        $type = Str::before($value, ',');

        return $type !== 'none' && ! Str::startsWith($type, 'serial');
    }
}
