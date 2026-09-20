<?php

namespace App\Services\Servers;

use App\Data\Cluster\ServerResourceData;
use App\Data\Server\Adoption\AdoptableGuestData;
use App\Data\Server\Adoption\AdoptableGuestListData;
use App\Data\Server\Adoption\AdoptionAddressData;
use App\Data\Server\Adoption\GuestAdoptionPreviewData;
use App\Data\Server\Adoption\UnreachableScopeData;
use App\Data\Server\Proxmox\Config\DiskData;
use App\Data\Server\Proxmox\Config\IpConfigData;
use App\Data\Server\Proxmox\Config\NetworkDeviceData;
use App\Data\Server\Proxmox\Config\ServerConfigData;
use App\Enums\Network\AddressOrigin;
use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Network\AddressVersion;
use App\Enums\Server\AddressAdoptionVerdict;
use App\Exceptions\Proxmox\RequestException;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\Node;
use App\Models\Server;
use App\Services\Addresses\AddressReachabilityService;
use App\Services\Proxmox\Cluster\ProxmoxResourceClient;
use App\Services\Proxmox\Server\ProxmoxConfigClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Adopting a guest that already exists on a registered node.
 *
 * Adoption, not an import form. The poll already sees every guest in every
 * cluster through `/cluster/resources`; anything there that Convoy does not own
 * by (cluster, vmid) is adoptable, so an operator picks one off a list instead
 * of retyping a node and a vmid the panel can already see.
 *
 * The one rule that governs everything here: **adoption never writes to the
 * guest.** No `smbios1` stamp, no `ipconfig` rewrite, no NIC rewrite, no
 * firewall write, no disk resize. The existing `should_create_vm: false` path
 * does all of those through VmSyncService and would destroy an adopted guest's
 * networking, which is why none of it is reused.
 *
 * The second rule: a refusal adopts the server anyway, flagged. One ambiguous
 * address out of four is not a reason to reject a whole guest; it is a reason
 * to leave that address unclaimed and name it for a human.
 *
 * Scope, deliberately: QEMU only, `net0` / `ipconfig0` only, one guest at a
 * time, registered nodes only. See docs/ipam-migration-research.md §6.
 */
class GuestAdoptionService
{
    public function __construct(
        private ProxmoxResourceClient $resources,
        private ProxmoxConfigClient $config,
        private AddressReachabilityService $reachability,
    ) {}

    /**
     * Every guest Convoy can see and does not own.
     *
     * One request per scope, not per node: every member of a cluster returns
     * the same `/cluster/resources`, so asking one reachable member answers for
     * all of them. A scope that cannot be asked is reported rather than
     * dropped, because an empty list and an unanswered question read the same
     * on screen and mean opposite things.
     */
    public function adoptable(): AdoptableGuestListData
    {
        $nodes = Node::query()->with('cluster')->orderBy('name')->get();

        $guests = [];
        $unreachable = [];
        $askedScopes = [];

        foreach ($nodes as $node) {
            $scope = $node->cluster_id !== null && ! $node->cluster->isStandalone()
                ? 'cluster:'.$node->cluster_id
                : 'node:'.$node->id;

            if (isset($askedScopes[$scope])) {
                continue;
            }

            try {
                $observed = $this->resources->setNode($node)->getResources();
            } catch (RequestException|ConnectionException $e) {
                $unreachable[] = new UnreachableScopeData(
                    nodeId: $node->id,
                    nodeName: $node->name,
                    reason: Str::limit($e->getMessage(), 160),
                );

                continue;
            }

            $askedScopes[$scope] = true;
            $guests = array_merge($guests, $this->unclaimed($observed, $nodes));
        }

        usort(
            $guests,
            fn (AdoptableGuestData $a, AdoptableGuestData $b) => [$a->nodeName, $a->vmid] <=> [$b->nodeName, $b->vmid],
        );

        return new AdoptableGuestListData(guests: $guests, unreachable: $unreachable);
    }

    /**
     * Read one guest and work out what adopting it would do, without doing any
     * of it.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function preview(Node $node, int $vmid): GuestAdoptionPreviewData
    {
        $config = $this->config->setNode($node)->setServer($this->stub($node, $vmid))->getConfig();

        $net0 = $config->networkDevices->firstWhere('id', 0);
        $ipconfig0 = $config->cloudinit->ipConfigs->get(0);

        $bridge = $net0 instanceof NetworkDeviceData ? $net0->bridge : null;
        $interface = $bridge === null ? null : $node->networkInterfaces()->where('name', $bridge)->first();
        $storage = $this->primaryStorage($node, $config);
        $addresses = $this->reconcile($node, $ipconfig0);
        $disk = $this->primaryDisk($config);

        return new GuestAdoptionPreviewData(
            nodeId: $node->id,
            nodeName: $node->name,
            vmid: $vmid,
            name: $config->name,
            cpuCount: max(1, $config->cpu->coreCount),
            memory: $config->memory,
            diskSize: $disk instanceof DiskData ? $disk->size : 0,
            bridge: $bridge,
            macAddress: $net0 instanceof NetworkDeviceData ? $net0->macAddress : null,
            vlanTag: $net0 instanceof NetworkDeviceData ? $net0->vlanTag : null,
            networkInterfaceId: $interface?->id,
            storageId: $storage[0],
            storageName: $storage[1],
            addresses: $addresses,
            hasUnmanagedIpConfig: $this->isUnmanaged($ipconfig0),
            ignoredInterfaces: $config->networkDevices
                ->filter(fn (NetworkDeviceData $device) => $device->id !== 0)
                ->map(fn (NetworkDeviceData $device) => 'net'.$device->id)
                ->values()
                ->all(),
            blockedReason: $this->blockedReason($config, $storage[0], $node, $vmid),
        );
    }

    /**
     * Apply one address verdict, given a server that now exists.
     *
     * Only the three claiming verdicts write anything, and each writes exactly
     * one row. Everything else is left for a human, which is what the flag on
     * the server is for.
     *
     * @return ?Address the row the server now holds
     */
    public function applyVerdict(Server $server, AdoptionAddressData $candidate): ?Address
    {
        if (! $candidate->verdict->claimsTheAddress()) {
            return null;
        }

        $block = $this->singleContainingBlock($server->node, $candidate->ip);

        if ($block === null) {
            return null;
        }

        $address = Address::query()
            ->where('address_block_id', $block->id)
            ->where('ip', $candidate->ip)
            ->first();

        if ($address === null) {
            $address = new Address;
            $address->address_block_id = $block->id;
            $address->ip = $candidate->ip;
            $address->prefix_length = $block->prefix_length_to;
            $address->origin = AddressOrigin::Imported;
        }

        $address->forceFill([
            'server_id' => $server->id,
            'state' => AddressState::Assigned,
            'state_reason' => null,
            'observed_at' => now(),
        ])->save();

        return $address;
    }

    /**
     * @param  Collection<int, ServerResourceData>  $observed
     * @param  Collection<int, Node>  $nodes
     * @return array<int, AdoptableGuestData>
     */
    private function unclaimed(Collection $observed, Collection $nodes): array
    {
        $byName = $nodes->keyBy('name');

        return $observed
            // Templates are not guests an operator runs, and adopting one would
            // make a server row nothing can start. Excluded entirely; see
            // docs/templates-handoff.md.
            ->reject(fn (ServerResourceData $guest) => $guest->isTemplate)
            ->filter(fn (ServerResourceData $guest) => $guest->nodeName !== null && $byName->has($guest->nodeName))
            ->reject(function (ServerResourceData $guest) use ($byName) {
                $node = $byName->get($guest->nodeName);

                // (cluster, vmid) is the identity PVE itself enforces, and
                // Server::isUniqueVmId already scopes it correctly for both a
                // clustered node and a standalone one.
                return ! Server::isUniqueVmId($node, $guest->vmid);
            })
            ->map(function (ServerResourceData $guest) use ($byName) {
                $node = $byName->get($guest->nodeName);

                return new AdoptableGuestData(
                    nodeId: $node->id,
                    nodeName: $node->name,
                    vmid: $guest->vmid,
                    name: $guest->name,
                    status: $guest->status,
                    cpuCount: $guest->maxCpuCount,
                    memory: $guest->maxMemory,
                    diskSize: $guest->maxDiskSpace,
                    uptimeInSeconds: $guest->uptimeInSeconds,
                    blockedReason: $guest->lockStatus === null
                        ? null
                        : sprintf('Proxmox has this guest locked (%s). Adopt it once the operation finishes.', $guest->lockStatus->value),
                );
            })
            ->values()
            ->all();
    }

    /**
     * Case-by-case reconciliation of `ipconfig0` against the address model.
     *
     * @return array<int, AdoptionAddressData>
     */
    private function reconcile(Node $node, ?IpConfigData $ipconfig): array
    {
        if ($ipconfig === null) {
            return [];
        }

        $candidates = [];

        foreach ([$ipconfig->ip, $ipconfig->ip6] as $cidr) {
            // `dhcp` and `auto` are a statement that something other than the
            // panel assigns this address. There is nothing to reconcile.
            if ($cidr === null || $cidr === '' || in_array(Str::lower($cidr), ['dhcp', 'auto'], true)) {
                continue;
            }

            [$ip, $prefix] = array_pad(explode('/', $cidr, 2), 2, null);

            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                continue;
            }

            $candidates[] = $this->verdictFor($node, $ip, (int) ($prefix ?? 32));
        }

        return $candidates;
    }

    private function verdictFor(Node $node, string $ip, int $prefixLength): AdoptionAddressData
    {
        $version = str_contains($ip, ':') ? AddressVersion::IPv6 : AddressVersion::IPv4;

        $make = fn (
            AddressAdoptionVerdict $verdict,
            string $reason,
            ?AddressBlock $block = null,
            ?string $conflicting = null,
        ) => new AdoptionAddressData(
            ip: $ip,
            prefixLength: $prefixLength,
            version: $version,
            gateway: $block?->gateway,
            verdict: $verdict,
            reason: $reason,
            blockName: $block?->name,
            poolName: $block?->addressBlockGroup?->name,
            conflictingServerName: $conflicting,
        );

        // Containment is scoped to what this node can actually route. An
        // unscoped test would happily match a block on a node the guest has no
        // path to, which is a different address that merely looks the same.
        $covering = $this->reachability->blocksReachableFrom($node)
            ->filter(fn (AddressBlock $block) => $block->version === $version && $block->containsAddress($ip))
            ->values();

        if ($covering->count() > 1) {
            return $make(
                AddressAdoptionVerdict::Ambiguous,
                sprintf('Covered by %d overlapping blocks (%s). Nothing resolves the tie, so it stays unclaimed.',
                    $covering->count(),
                    $covering->pluck('name')->join(', ', ' and '),
                ),
            );
        }

        if ($covering->isEmpty()) {
            // It may still be inside a block this node cannot reach, which is a
            // different and more actionable problem than "nobody manages it".
            $elsewhere = AddressBlock::query()
                ->get()
                ->first(fn (AddressBlock $block) => $block->version === $version && $block->containsAddress($ip));

            if ($elsewhere !== null) {
                return $make(
                    AddressAdoptionVerdict::Unreachable,
                    sprintf('Inside "%s", which no interface on %s is attached to. Attach the pool to this node\'s bridge, then adopt again.',
                        $elsewhere->name,
                        $node->name,
                    ),
                    $elsewhere,
                );
            }

            return $make(
                AddressAdoptionVerdict::Unmanaged,
                'Outside every address block Convoy manages. The guest keeps it; the panel will not track it.',
            );
        }

        $block = $covering->first();
        $existing = Address::query()
            ->where('address_block_id', $block->id)
            ->where('ip', $ip)
            ->with('server')
            ->first();

        if ($existing === null) {
            return $make(
                AddressAdoptionVerdict::Mint,
                sprintf('Inside "%s" with no row yet. It gets written and assigned.', $block->name),
                $block,
            );
        }

        if ($existing->state === AddressState::Assigned) {
            $holder = $existing->server instanceof Server ? $existing->server->name : null;

            return $make(
                AddressAdoptionVerdict::Conflict,
                sprintf('Already assigned to "%s". Nothing is taken from it; adopt and resolve which server really has this address.',
                    $holder ?? 'another server',
                ),
                $block,
                $holder,
            );
        }

        if ($existing->state === AddressState::Reserved) {
            return $existing->state_reason === AddressStateReason::System
                ? $make(
                    AddressAdoptionVerdict::SystemReserved,
                    sprintf('This is a structural address of "%s" (network, broadcast or gateway) and a guest is using it. Worth looking at on the node.', $block->name),
                    $block,
                )
                : $make(
                    AddressAdoptionVerdict::AdminReserved,
                    'Held out of the pool. Unreserve it, then adopt again to claim it.',
                    $block,
                );
        }

        return $make(
            AddressAdoptionVerdict::Claim,
            sprintf('Free in "%s". It gets assigned to this server.', $block->name),
            $block,
        );
    }

    /** The single reachable block covering $ip, or null when there is not exactly one. */
    private function singleContainingBlock(Node $node, string $ip): ?AddressBlock
    {
        $version = str_contains($ip, ':') ? AddressVersion::IPv6 : AddressVersion::IPv4;

        $covering = $this->reachability->blocksReachableFrom($node)
            ->filter(fn (AddressBlock $block) => $block->version === $version && $block->containsAddress($ip))
            ->values();

        return $covering->count() === 1 ? $covering->first() : null;
    }

    private function isUnmanaged(?IpConfigData $ipconfig): bool
    {
        if ($ipconfig === null) {
            return true;
        }

        $values = array_filter([$ipconfig->ip, $ipconfig->ip6]);

        if ($values === []) {
            return true;
        }

        foreach ($values as $value) {
            if (in_array(Str::lower($value), ['dhcp', 'auto'], true)) {
                return true;
            }
        }

        return false;
    }

    private function primaryDisk(ServerConfigData $config): ?DiskData
    {
        return $config->disks->sortByDesc(fn (DiskData $disk) => $disk->size)->first();
    }

    /**
     * The Convoy storage row the guest's largest disk lives on.
     *
     * @return array{?int, ?string}
     */
    private function primaryStorage(Node $node, ServerConfigData $config): array
    {
        $disk = $this->primaryDisk($config);

        if ($disk === null) {
            return [null, null];
        }

        // A volid is `<storage>:<volume>`; the head is the storage PVE knows.
        $name = Str::before($disk->volume, ':');

        return [$node->storages()->where('storages.name', $name)->first()?->id, $name];
    }

    private function blockedReason(ServerConfigData $config, ?int $storageId, Node $node, int $vmid): ?string
    {
        if ($config->isTemplate) {
            return 'This is a template, not a guest. Templates are managed as images.';
        }

        if ($config->lockStatus !== null) {
            return sprintf('Proxmox has this guest locked (%s). Adopt it once the operation finishes.', $config->lockStatus->value);
        }

        if (! Server::isUniqueVmId($node, $vmid)) {
            return sprintf('VMID %d is already held by a server in Convoy.', $vmid);
        }

        if ($storageId === null) {
            return 'The disk this guest boots from is on a storage Convoy has not discovered on this node.';
        }

        return null;
    }

    /**
     * A throwaway Server carrying only what the config client needs from one:
     * the vmid it puts in the URL. Never saved.
     */
    private function stub(Node $node, int $vmid): Server
    {
        $server = new Server;
        $server->vmid = $vmid;
        $server->setRelation('node', $node);

        return $server;
    }
}
