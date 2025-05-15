<?php

namespace App\Models;

use App\Enums\Network\AddressVersion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AddressBlock extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public static array $validationRules = [
        'address_block_group_id' => 'required|integer|exists:address_block_groups,id',
        'name' => 'nullable|string|max:40',
        'description' => 'nullable|string|max:191',
        'version' => 'in:ipv4,ipv6|required',
        'base_ip' => 'required|ip',
        'gateway' => 'nullable|ip',
        'mac_address' => 'nullable|mac_address',
        'prefix_length_from' => 'required|integer|min:0|max:128',
        'prefix_length_to' => 'required|integer|min:0|max:128',
    ];

    public function casts(): array
    {
        return [
            'version' => AddressVersion::class,
        ];
    }

    public function addressBlockGroup(): BelongsTo
    {
        return $this->belongsTo(AddressBlockGroup::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
