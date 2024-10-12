<?php

namespace Convoy\Models;

use Convoy\Casts\NullableEncrypter;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coterm extends Model
{
    /**
     * The constants for generating Coterm secret keys
     */
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

    public static array $validationRules = [
        'name' => 'required|string|max:191',
        'is_tls_enabled' => 'required|boolean',
        'fqdn' => 'required|string|max:191',
        'port' => 'required|integer|min:1|max:65535',
        'token_id' => 'required|string|max:191',
        'token' => 'required|string|max:191',
    ];

    protected function casts(): array
    {
        return [
            'is_tls_enabled' => 'boolean',
            'coterm_token' => NullableEncrypter::class,
        ];
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
