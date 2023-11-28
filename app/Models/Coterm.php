<?php

namespace Convoy\Models;

use Convoy\Casts\NullableEncrypter;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coterm extends Model
{
    public const COTERM_TOKEN_ID_LENGTH = 16;
    public const COTERM_TOKEN_LENGTH = 64;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'token_id', 'token',
    ];

    protected $casts = [
        'is_tls_enabled' => 'boolean',
        'coterm_token' => NullableEncrypter::class,
    ];

    public static $validationRules = [
        'name' => 'required|string|max:191',
        'is_tls_enabled' => 'required|boolean',
        'fqdn' => 'required|string|max:191',
        'port' => 'required|integer|min:1|max:65535',
        'token_id' => 'required|string|max:191',
        'token' => 'required|string|max:191',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }
}
