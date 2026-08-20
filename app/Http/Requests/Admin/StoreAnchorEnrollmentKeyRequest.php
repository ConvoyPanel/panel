<?php

namespace App\Http\Requests\Admin;

use App\Enums\Anchor\AnchorMode;
use App\Http\Requests\BaseApiRequest;
use App\Services\Anchor\AnchorEnrollmentKeyService;
use Illuminate\Validation\Rules\Enum;

class StoreAnchorEnrollmentKeyRequest extends BaseApiRequest
{
    /** A year. Long enough for a machine image, short enough to still be a decision. */
    private const MAX_TTL_MINUTES = 525600;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            // Absent or null admits either mode.
            'mode' => ['sometimes', 'nullable', new Enum(AnchorMode::class)],
            // Explicit null means unlimited. See maxUses().
            'max_uses' => ['sometimes', 'nullable', 'integer', 'min:1'],
            // Explicit null means it never expires. See expiresInMinutes().
            'expires_in_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:'.self::MAX_TTL_MINUTES],
        ];
    }

    public function mode(): ?AnchorMode
    {
        return $this->enum('mode', AnchorMode::class);
    }

    /**
     * Null is "unlimited" and has to be asked for by name.
     *
     * Omitting the field gives a single-use key, so the dangerous shape is the
     * one you cannot reach by forgetting a parameter -- which is the only
     * reason `sometimes|nullable` is worth the subtlety here.
     */
    public function maxUses(): ?int
    {
        if (! $this->has('max_uses')) {
            return 1;
        }

        return $this->input('max_uses') === null ? null : $this->integer('max_uses');
    }

    /** Same contract as {@see maxUses()}: absent is the safe default, null is "never". */
    public function expiresInMinutes(): ?int
    {
        if (! $this->has('expires_in_minutes')) {
            return AnchorEnrollmentKeyService::DEFAULT_TTL_MINUTES;
        }

        return $this->input('expires_in_minutes') === null
            ? null
            : $this->integer('expires_in_minutes');
    }
}
