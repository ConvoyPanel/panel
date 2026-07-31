<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $node_id
 * @property string $name
 * @property ?string $description
 * @property bool $is_vlan_aware
 * @property ?int $vlan_tag
 * @property ?int $servers_count
 * @property Collection<int, AddressBlockGroup> $addressBlockGroups
 * @property Collection<int, Server> $servers
 */
class NetworkInterface extends Model
{
    use HasFactory;

    /**
     * Resolved VLAN usage (tag => server count) for this bridge.
     *
     * A declared property rather than an attribute, so Eloquent's `__set` never
     * sees it and it can't be mistaken for a column on save. It only exists so
     * a list can fill it from one batched `vlanUsageFor()` call instead of
     * letting each interface query for itself; leave it null and `vlanUsage()`
     * still answers correctly on its own.
     */
    public ?Collection $resolvedVlanUsage = null;

    public $timestamps = false;

    protected $guarded = [
        'id',
    ];

    public static array $validationRules = [
        'node_id' => 'required|integer|exists:nodes,id',
        'name' => 'required|string|min:1|max:40',
        'description' => 'nullable|string|max:191',
        'is_vlan_aware' => 'sometimes|boolean',
        'vlan_tag' => 'nullable|integer|min:1|max:4094',
    ];

    protected function casts(): array
    {
        return [
            'is_vlan_aware' => 'boolean',
            'vlan_tag' => 'integer',
        ];
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Servers attached to this interface. The FK is `nullOnDelete`, so a
     * deleted interface leaves its servers behind unattached rather than
     * cascading — this counts only what is currently on the bridge.
     *
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * VLANs declared on this bridge. Declaration is independent of use — a
     * freshly configured trunk can have VLANs with no servers on them yet.
     *
     * @return HasMany<Vlan, $this>
     */
    public function vlans(): HasMany
    {
        return $this->hasMany(Vlan::class);
    }

    /**
     * How many servers resolve to each tag on this bridge, keyed by tag.
     *
     * A server on an aware bridge with a null `vlan_tag` inherits the bridge
     * default, so the grouping has to be on the resolved value — grouping on
     * `servers.vlan_tag` alone would file those under "untagged" while Proxmox
     * has them on the bridge's tag. A non-aware bridge forces a null tag for
     * every server on it, so there is nothing to group.
     *
     * @return Collection<int, int>
     */
    public function vlanUsage(): Collection
    {
        return $this->resolvedVlanUsage
            ??= static::vlanUsageFor([$this])->get($this->id) ?? collect();
    }

    /**
     * The same counts for many bridges in one query, since a node's whole
     * interface list needs them at once.
     *
     * The `COALESCE` is what resolves inheritance, and filtering on it (rather
     * than on `servers.vlan_tag`) is also what keeps a pure trunk's untagged
     * servers out: with no bridge default to fall back to, they coalesce to
     * null and drop away.
     *
     * @param  iterable<NetworkInterface>  $interfaces
     * @return Collection<int, Collection<int, int>> interface id => tag => count
     */
    public static function vlanUsageFor(iterable $interfaces): Collection
    {
        $ids = collect($interfaces)
            ->filter(fn (self $interface) => $interface->is_vlan_aware)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Server::query()
            ->join(
                'network_interfaces',
                'servers.network_interface_id',
                '=',
                'network_interfaces.id',
            )
            ->whereIn('network_interfaces.id', $ids)
            ->selectRaw('network_interfaces.id as interface_id')
            ->selectRaw('COALESCE(servers.vlan_tag, network_interfaces.vlan_tag) as resolved_tag')
            ->selectRaw('COUNT(*) as total')
            ->whereRaw('COALESCE(servers.vlan_tag, network_interfaces.vlan_tag) IS NOT NULL')
            ->groupBy('network_interfaces.id', 'resolved_tag')
            ->get()
            ->groupBy('interface_id')
            ->map(fn (Collection $rows) => $rows->mapWithKeys(
                fn ($row) => [(int) $row->resolved_tag => (int) $row->total],
            ));
    }

    /**
     * @return BelongsToMany<AddressBlockGroup, $this>
     */
    public function addressBlockGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            AddressBlockGroup::class,
            'address_block_group_to_network_interface',
            'network_interface_id',
            'address_block_group_id'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
