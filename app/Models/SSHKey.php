<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SSHKey extends Model
{
    use HasFactory;

    protected $table = 'ssh_keys';

    protected $fillable = [
        'name',
        'public_key',
    ];

    public static $validationRules = [
        'name' => 'required|string|max:40',
        'public_key' => 'required|string|max:500',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
