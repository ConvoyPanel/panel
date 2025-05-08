<?php

namespace App\Models;

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

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
