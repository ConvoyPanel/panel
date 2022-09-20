<?php

namespace Convoy\Models;

use Convoy\Enums\Network\AddressType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Enum;

class IPAddress extends Model
{
    use HasFactory;

    protected $table = 'ip_addresses';

    protected $fillable = ['server_id', 'node_id', 'address', 'cidr', 'gateway', 'mac_address', 'type'];

    public static function getRules()
    {
        return [
            'server_id' => ['exists:servers,id', 'nullable'],
            'node_id' => ['exists:nodes,id', 'required'],
            'type' => [new Enum(AddressType::class), 'required'],
            'address' => ['ip'],
            'cidr' => ['numeric', 'required'],
            'gateway' => ['ip'],
            'mac_address' => ['mac_address', 'nullable'],
        ];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
