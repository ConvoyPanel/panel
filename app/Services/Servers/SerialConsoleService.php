<?php

namespace App\Services\Servers;

use App\Data\Server\SerialConsoleData;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxConfigClient;
use Illuminate\Http\Client\ConnectionException;

/**
 * The serial device the terminal console attaches to.
 *
 * Proxmox's `termproxy` opens a serial terminal only against a `serial[n]`
 * device on the VM; without one there is nothing to attach to and the console
 * closes without ever carrying a frame. Every VM has a VGA device, which is why
 * the display console has never needed any of this.
 *
 * Note this only covers the hypervisor half. A guest with a serial port but no
 * login prompt bound to it (`console=ttyS0` on the kernel command line) still
 * connects to silence -- that part lives inside the image and cannot be fixed
 * from out here.
 */
readonly class SerialConsoleService
{
    /** PVE allows serial0..serial3; the console only ever needs the first. */
    private const DEVICE = 'serial0';

    /** A host-side unix socket, which is what `termproxy` connects to. */
    private const MODE = 'socket';

    public function __construct(private ProxmoxConfigClient $configClient) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function status(Server $server): SerialConsoleData
    {
        $rows = $this->configClient->setServer($server)->getPendingConfig();

        foreach ($rows as $row) {
            if (($row['key'] ?? null) !== self::DEVICE) {
                continue;
            }

            // `value` is what the running guest has; `pending` is what it will
            // have at next boot. A row with only the latter is the mid-state
            // between enabling and restarting.
            $live = ($row['value'] ?? '') !== '';
            $queued = ($row['pending'] ?? '') !== '';

            return new SerialConsoleData(
                enabled: $live,
                restartRequired: ! $live && $queued,
            );
        }

        return new SerialConsoleData(enabled: false, restartRequired: false);
    }

    /**
     * Add the serial device, unless the VM already has one.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function enable(Server $server): SerialConsoleData
    {
        $status = $this->status($server);

        if ($status->enabled || $status->restartRequired) {
            return $status;
        }

        $this->configClient->setServer($server)->update([
            self::DEVICE => self::MODE,
        ]);

        return $this->status($server);
    }

    /**
     * Add the serial device to a config payload when the VM has none.
     *
     * Called from the deploy-time sync so servers built from here on out have a
     * working terminal console without anyone being told to go and add one.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function withDevice(array $payload, bool $alreadyPresent): array
    {
        if ($alreadyPresent) {
            return $payload;
        }

        return [...$payload, self::DEVICE => self::MODE];
    }
}
