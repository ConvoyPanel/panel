<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;
    
    protected $table = 'ip_addresses';

    protected $guarded = ['id', 'updated_at', 'created_at'];

    public static array $validationRules = [
        // `integer` before `exists`: Laravel skips `exists` once an earlier rule has
        // failed, and Postgres refuses to compare a bigint id with text.
        'address_pool_id' => ['required', 'integer', 'exists:address_pools,id'],
        'server_id' => ['nullable', 'integer', 'exists:servers,id'],
        'type' => ['in:ipv4,ipv6', 'required'],
        'address' => ['ip'],
        'cidr' => ['numeric', 'min:0', 'max:128', 'required'],
        'gateway' => ['ip'],
        'mac_address' => ['mac_address', 'nullable'],
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
