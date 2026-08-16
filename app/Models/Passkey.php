<?php

namespace App\Models;

use App\Http\Requests\Auth\Passkeys\RenamePasskeyRequest;
use App\Support\Passkeys\AuthenticatorAaguids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\LaravelPasskeys\Models\Passkey as SpatiePasskey;
use Webauthn\PublicKeyCredentialSource;

/**
 * Convoy's passkey model. Extends the spatie/laravel-passkeys model so we adopt
 * its package-default storage (a serialized {@see PublicKeyCredentialSource} in
 * the `data` column) while keeping Convoy's existing schema and behavior:
 *
 *   - the legacy `user_id` foreign key (via {@see User::passkeys()}), instead of
 *     the package's `authenticatable_id` morph column;
 *   - `id` route-model binding (Eloquent's default);
 *   - no `updated_at` column (the `passkeys` table only has `created_at`).
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $credential_id
 * @property PublicKeyCredentialSource $data
 * @property User $user
 */
class Passkey extends SpatiePasskey
{
    const UPDATED_AT = null;

    /**
     * Also the width the authenticator display names in {@see AuthenticatorAaguids} are trimmed
     * to, so a name we assign automatically is always one a rename would accept back.
     */
    public const NAME_MAX_LENGTH = 40;

    protected $hidden = [
        'credential_id',
        'data',
    ];

    /**
     * Kept for {@see RenamePasskeyRequest}.
     */
    public static array $validationRules = [
        'name' => 'required|string|max:'.self::NAME_MAX_LENGTH,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
