<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SSHKey extends Model
{
    protected $table = 'ssh_keys';

    protected $fillable = [
        'name',
        'public_key',
    ];

    public static array $validationRules = [
        'name' => 'required|string|max:40',
        'public_key' => 'required|string|max:500',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** SSH keys have no uuid; bind by primary key (the base Model defaults to uuid). */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
