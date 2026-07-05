<?php

namespace App\Models;

use App\Services\Auth\PasskeySerializer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $credential_id
 * @property User $user
 */
class Passkey extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [
        'id',
        'created_at',
    ];

    protected $hidden = [
        'credential_id',
        'data',
    ];

    public static array $validationRules = [
        'name' => 'required|string|max:40',
        'data' => 'required|string',
        'last_used_at' => 'nullable|date',
    ];

    public function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function data(): Attribute
    {
        $serializer = PasskeySerializer::make();

        return new Attribute(
            get: fn (string $value) => $serializer->fromJson(
                $value,
                PublicKeyCredentialSource::class
            ),
            set: fn (PublicKeyCredentialSource $value) => [
                'credential_id' => Base64UrlSafe::encodeUnpadded($value->publicKeyCredentialId),
                'data' => $serializer->toJson($value),
            ],
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function publicKeyCredentialDescriptor(): PublicKeyCredentialDescriptor
    {
        return new PublicKeyCredentialDescriptor(
            type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            id: $this->credential_id,
        );
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
