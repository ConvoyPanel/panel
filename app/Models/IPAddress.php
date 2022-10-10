<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class IPAddress extends Model
{
    use HasFactory;

    protected $table = 'ip_addresses';

    protected $fillable = ['server_id', 'node_id', 'address', 'cidr', 'gateway', 'mac_address', 'type'];

    public static $validationRules = [
        'server_id' => ['exists:servers,id', 'nullable'],
        'node_id' => ['exists:nodes,id', 'required'],
        'type' => ['in:ipv4,ipv6', 'required'],
        'address' => ['ip'],
        'cidr' => ['numeric', 'required'],
        'gateway' => ['ip'],
        'mac_address' => ['mac_address', 'nullable'],
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
