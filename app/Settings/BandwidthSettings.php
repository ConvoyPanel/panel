<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Panel-wide bandwidth defaults. Currently the top ("global") tier of the
 * overage-penalty cascade (server override -> node override -> these defaults);
 * see docs/bandwidth-rate-limiting-plan.md §5.
 *
 * The action is stored as a plain string ('throttle' | 'disconnect') rather than
 * a backed enum so the stored shape never changes; the resolver is responsible
 * for turning it into the typed penalty object.
 */
class BandwidthSettings extends Settings
{
    /**
     * What happens to a server that exceeds its monthly bandwidth quota, unless
     * a node- or server-level override says otherwise.
     *
     * - 'throttle'   -> cap every NIC at {@see $overage_rate}
     * - 'disconnect' -> set link_down on every NIC (reversible; the guest keeps
     *                   the NIC but loses carrier)
     */
    public string $overage_action = 'throttle';

    /**
     * Throttle target in bytes/s, applied when $overage_action is 'throttle'.
     * Defaults to 1 MB/s (Proxmox's floor is 1 MB/s; see the plan §7.1).
     */
    public int $overage_rate = 1_000_000;

    public static function group(): string
    {
        return 'bandwidth';
    }
}
