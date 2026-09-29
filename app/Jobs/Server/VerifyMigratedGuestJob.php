<?php

namespace App\Jobs\Server;

use App\Exceptions\Proxmox\RequestException;
use App\Models\DeploymentStep;
use App\Models\ServerMigrationTransfer;
use App\Services\Proxmox\Server\ProxmoxMigrationClient;
use App\Traits\Jobs\FailsWithStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use RuntimeException;

use function now;

/**
 * Owns the `verify-guest` step, and it is the step the whole transport is
 * arranged around.
 *
 * The source guest is not destroyed until this passes. Everything before it is
 * reversible by restarting a guest that was never touched; everything after it
 * is not. So this asks Proxmox on the destination directly rather than
 * trusting the agent's own report that its `qmrestore` exited zero: the agent
 * can only tell us what its subprocess said, and what matters is whether the
 * destination node will now boot a guest with this server's disks on it.
 *
 * Three questions, in the order they can fail: is there a config at that VMID,
 * does it have the disks the source had, and are they the same size. A restore
 * that dropped a volume answers the first two and fails the third, which is
 * exactly the failure that is invisible until someone boots the guest.
 */
class VerifyMigratedGuestJob implements ShouldQueue
{
    use Dispatchable, FailsWithStep, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(10);
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    /** PVE's own disk keys. `efidisk`/`tpmstate` are state, not data, but a
     * guest that lost one does not boot either. */
    private const DISK_PREFIXES = ['scsi', 'virtio', 'sata', 'ide', 'efidisk', 'tpmstate'];

    public function __construct(
        #[WithoutRelations]
        public DeploymentStep $step,
        public int $transferId,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(ProxmoxMigrationClient $client): void
    {
        $transfer = ServerMigrationTransfer::findOrFail($this->transferId);

        $this->step->run(function () use ($client, $transfer) {
            $destination = $client->getGuestConfig($transfer->destinationNode, $transfer->destination_vmid);

            if ($destination === []) {
                throw new RuntimeException(sprintf(
                    '%s reports no guest at VMID %d after the restore.',
                    $transfer->destinationNode->name,
                    $transfer->destination_vmid,
                ));
            }

            // Read afterwards, not before: the source guest is stopped and
            // untouched, so its config is the same now as it was at export,
            // and reading it here keeps the comparison in one place.
            $expected = $this->disks($client->getGuestConfig($transfer->sourceNode, $transfer->source_vmid));
            $actual = $this->disks($destination);

            $missing = array_diff(array_keys($expected), array_keys($actual));

            if ($missing !== []) {
                throw new RuntimeException(sprintf(
                    'The guest restored on %s is missing %s.',
                    $transfer->destinationNode->name,
                    implode(', ', $missing),
                ));
            }

            foreach ($expected as $key => $bytes) {
                // A size of zero means PVE did not state one on either side,
                // which is normal for a raw volume whose size lives on the
                // storage. There is nothing to compare, so do not invent a
                // mismatch out of two unknowns.
                if ($bytes === 0 || $actual[$key] === 0 || $bytes === $actual[$key]) {
                    continue;
                }

                throw new RuntimeException(sprintf(
                    '%s came back the wrong size on %s.',
                    $key,
                    $transfer->destinationNode->name,
                ));
            }

            $transfer->forceFill(['verified_at' => now()])->save();
        });
    }

    /**
     * Disk keys mapped to their declared size in bytes.
     *
     * Parsed rather than string-compared because `vzdump` and `qmrestore` are
     * free to write the same size differently -- `32G` on one side and
     * `33554432K` on the other is a match, and comparing the raw values would
     * call it a corrupted restore.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, int>
     */
    private function disks(array $config): array
    {
        $disks = [];

        foreach ($config as $key => $value) {
            // PVE's config map holds ints and bools too (`cores`, `agent`);
            // only a string can be a volume reference.
            if (! is_string($value)) {
                continue;
            }

            $isDisk = false;

            foreach (self::DISK_PREFIXES as $prefix) {
                if (preg_match('/^'.$prefix.'\d+$/', $key) === 1) {
                    $isDisk = true;

                    break;
                }
            }

            // `scsi0: none,media=cdrom` is a drive bay, not a disk, and an
            // empty one does not survive a restore as anything worth failing
            // a migration over.
            if (! $isDisk || str_contains($value, 'media=cdrom')) {
                continue;
            }

            $disks[$key] = $this->bytes($value);
        }

        return $disks;
    }

    /** `local-lvm:vm-100-disk-0,size=32G` -> 34359738368. Zero when unstated. */
    private function bytes(string $value): int
    {
        if (preg_match('/(?:^|,)size=(\d+)([KMGT]?)/i', $value, $matches) !== 1) {
            return 0;
        }

        $multiplier = match (strtoupper($matches[2])) {
            'K' => 1024,
            'M' => 1024 ** 2,
            'G' => 1024 ** 3,
            'T' => 1024 ** 4,
            default => 1,
        };

        return (int) $matches[1] * $multiplier;
    }
}
