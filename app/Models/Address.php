<?php

namespace App\Models;

use App\Enums\Network\AddressVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public static array $validationRules = [
        'address_block_id' => ['exists:address_blocks,id', 'required'],
        'server_id' => ['exists:servers,id', 'nullable'],
        'ip' => ['ip'],
        'prefix_length' => ['numeric', 'min:0', 'max:128', 'required'],
    ];

    public function addressBlock(): BelongsTo
    {
        return $this->belongsTo(AddressBlock::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getVersionAttribute(): AddressVersion
    {
        return $this->addressBlock->version;
    }

    public function getGatewayAttribute(): ?string
    {
        return $this->addressBlock->gateway;
    }

    public function getMacAddressAttribute(): ?string
    {
        return $this->addressBlock->mac_address;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
