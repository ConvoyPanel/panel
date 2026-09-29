<?php

namespace App\Actions\Server;

use App\Data\Server\Adoption\AdoptionAddressData;
use App\Data\Server\Adoption\GuestAdoptionPreviewData;
use App\Enums\Network\AddressVersion;
use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Service\Server\AdoptionRefusedException;
use App\Models\Address;
use App\Models\Node;
use App\Models\Server;
use App\Services\Servers\GuestAdoptionService;
use App\Services\Servers\ServerCreationService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

use function now;

/**
 * Turns an observed guest into a server row, and nothing else.
 *
 * Everything written here is a database write. The guest is not touched: not
 * its `smbios1`, not its `ipconfig0`, not its NIC, not its firewall, not its
 * disks. That rules out reusing ServerCreationService's build path entirely,
 * because a deployment is exactly the thing that would write to it.
 *
 * The consequence to know about is that `servers.smbios_uuid` stays null on an
 * adopted server. ServerPlacementService treats a null uuid as "predates
 * stamping" and falls back to (cluster, vmid), which PVE enforces unique, so
 * placement reconciliation still works. Stamping it would be a write to the
 * guest and is left to the operator.
 *
 * Any address the reconciliation could not resolve leaves the server flagged.
 * The server exists either way: the alternative, refusing a whole guest because
 * one of its addresses is ambiguous, is the all-or-nothing behaviour that makes
 * an import feature go unused.
 */
class AdoptGuestAction
{
    public function __construct(
        private GuestAdoptionService $adoption,
        private ServerCreationService $creation,
        private ConnectionInterface $connection,
    ) {}

    /**
     * @param  array{user_id: int, name?: ?string, hostname?: ?string}  $input
     *
     * @throws AdoptionRefusedException
     */
    public function execute(Node $node, int $vmid, array $input): Server
    {
        $preview = $this->adoption->preview($node, $vmid);

        if ($preview->blockedReason !== null) {
            throw new AdoptionRefusedException($preview->blockedReason);
        }

        return $this->connection->transaction(function () use ($node, $vmid, $input, $preview) {
            $uuid = $this->creation->generateUniqueUuidCombo();

            $server = Server::forceCreate([
                'uuid' => $uuid,
                'uuid_short' => substr($uuid, 0, 8),
                'user_id' => $input['user_id'],
                'node_id' => $node->id,
                'network_interface_id' => $preview->networkInterfaceId,
                'storage_id' => $preview->storageId,
                'vmid' => $vmid,
                'hostname' => $this->hostname($input, $preview),
                'name' => $input['name'] ?? $preview->name ?? ('vm-'.$vmid),
                'description' => null,
                // Already built, by definition. Nothing is going to install it.
                'lifecycle' => ServerLifecycle::READY,
                'cpu' => $preview->cpuCount,
                'memory' => $preview->memory,
                'disk' => $preview->diskSize,
                'backup_count_limit' => 0,
                'backup_size_limit' => 0,
                'bandwidth_limit' => 0,
                'speed_limit' => null,
                'bandwidth_reset_day' => now()->day,
                'vlan_tag' => $preview->vlanTag,
                // The guest's addresses are its own until an operator says
                // otherwise. False whenever ipconfig0 is dhcp/auto/absent, or
                // whenever any observed address stayed unclaimed: writing
                // ipconfig0 from a partial claim would take an address away
                // from a running guest.
                'ipconfig_managed' => $preview->hasUnmanagedIpConfig
                    ? false
                    : $this->everyAddressClaimed($preview),
            ]);

            $server->disks()->create([
                'storage_id' => $preview->storageId,
                'size' => $preview->diskSize,
                'interface' => null,
                'is_primary' => true,
                'disk_index' => 0,
            ]);

            $claimed = collect($preview->addresses)
                ->map(fn (AdoptionAddressData $candidate) => $this->adoption->applyVerdict($server, $candidate))
                ->filter()
                ->values();

            $server->forceFill([
                'primary_ipv4_address_id' => $claimed->first(
                    fn (Address $address) => $address->version === AddressVersion::IPv4,
                )?->id,
                'primary_ipv6_address_id' => $claimed->first(
                    fn (Address $address) => $address->version === AddressVersion::IPv6,
                )?->id,
            ])->save();

            $unresolved = $this->unresolved($preview);

            if ($unresolved !== null) {
                $server->forceFill(['flagged_at' => now(), 'flag_reason' => $unresolved])->save();
            }

            return $server;
        });
    }

    private function everyAddressClaimed(GuestAdoptionPreviewData $preview): bool
    {
        return $preview->addresses !== []
            && collect($preview->addresses)
                ->every(fn (AdoptionAddressData $candidate) => $candidate->verdict->claimsTheAddress());
    }

    /** One sentence naming every address the panel refused to claim, or null. */
    private function unresolved(GuestAdoptionPreviewData $preview): ?string
    {
        $unclaimed = collect($preview->addresses)
            ->reject(fn (AdoptionAddressData $candidate) => $candidate->verdict->claimsTheAddress());

        if ($unclaimed->isEmpty()) {
            return null;
        }

        return Str::limit(sprintf(
            'Adopted from %s with %s unclaimed: %s',
            $preview->nodeName,
            $unclaimed->count() === 1 ? 'one address' : $unclaimed->count().' addresses',
            $unclaimed->map(fn (AdoptionAddressData $candidate) => $candidate->ip.' ('.$candidate->verdict->value.')')->join(', '),
        ), 188);
    }

    /**
     * @param  array{user_id: int, name?: ?string, hostname?: ?string}  $input
     */
    private function hostname(array $input, GuestAdoptionPreviewData $preview): string
    {
        $hostname = $input['hostname'] ?? $preview->name ?? ('vm-'.$preview->vmid);

        return Str::limit(Str::slug($hostname) ?: ('vm-'.$preview->vmid), 60, '');
    }
}
