<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A VLAN declared on a VLAN-aware bridge.
 *
 * This is a registry, not the source of truth for what Proxmox is told: the tag
 * a server actually gets is still resolved from `servers.vlan_tag` falling back
 * to `network_interfaces.vlan_tag` (see ServerNetworkService). A row here gives
 * a tag a name and lets it exist before any server uses it — nothing more. A
 * server may therefore carry a tag with no matching row; treat that as an
 * undeclared VLAN rather than an error.
 *
 * @property int $id
 * @property int $network_interface_id
 * @property int $tag
 * @property ?string $name
 * @property ?string $description
 * @property ?int $servers_count
 */
class Vlan extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public static array $validationRules = [
        'tag' => 'required|integer|min:1|max:4094',
        'name' => 'nullable|string|max:40',
        'description' => 'nullable|string|max:191',
    ];

    protected function casts(): array
    {
        return [
            'tag' => 'integer',
        ];
    }

    public function networkInterface(): BelongsTo
    {
        return $this->belongsTo(NetworkInterface::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
