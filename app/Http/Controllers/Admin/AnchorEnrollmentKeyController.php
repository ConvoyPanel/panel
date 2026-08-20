<?php

namespace App\Http\Controllers\Admin;

use App\Data\Anchor\AnchorEnrollmentKeyData;
use App\Data\PaginationMeta;
use App\Enums\Anchor\EnrollmentKeyStatus;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\StoreAnchorEnrollmentKeyRequest;
use App\Models\AnchorEnrollmentKey;
use App\Services\Anchor\AnchorEnrollmentKeyService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AnchorEnrollmentKeyController
{
    public function __construct(
        private AnchorEnrollmentKeyService $keys,
    ) {}

    public function index(Request $request)
    {
        $keys = QueryBuilder::for(AnchorEnrollmentKey::query())
            ->with('createdBy')
            ->defaultSort('-id')
            ->allowedFilters([
                'name',
                AllowedFilter::exact('mode'),
                // Status is derived from three columns and the clock, so it
                // cannot be an exact filter on one of them.
                AllowedFilter::callback(
                    'usable',
                    fn ($query, $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
                        ? $query->usable()
                        : $query->whereNot(fn ($query) => $query->usable()),
                ),
            ])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($keys, AnchorEnrollmentKeyData::class);
    }

    public function store(StoreAnchorEnrollmentKeyRequest $request)
    {
        $issued = $this->keys->issue(
            name: $request->string('name')->toString(),
            mode: $request->mode(),
            maxUses: $request->maxUses(),
            expiresInMinutes: $request->expiresInMinutes(),
            actor: $request->user(),
        );

        // The terms of the key are exactly what makes it dangerous, so they are
        // recorded in full. The token itself never is.
        Audit::record(
            AuditEvent::ADMIN_ANCHOR_ENROLLMENT_KEY_CREATED,
            subject: $issued->key,
            properties: [
                'name' => $issued->key->name,
                'mode' => $issued->key->mode?->value,
                'max_uses' => $issued->key->max_uses,
                'expires_at' => $issued->key->expires_at?->toIso8601String(),
            ],
        );

        return AnchorEnrollmentKeyData::fromModel($issued->key, $issued->token);
    }

    /**
     * Withdraw a key without erasing it.
     *
     * Revoking is the remediation, so it must leave behind the record of what
     * was admitted while the key was live. Erasure is a separate, narrower
     * action -- see {@see destroy()}.
     */
    public function revoke(AnchorEnrollmentKey $enrollmentKey)
    {
        if ($enrollmentKey->revoked_at === null) {
            $enrollmentKey->update(['revoked_at' => now()]);
        }

        Audit::record(
            AuditEvent::ADMIN_ANCHOR_ENROLLMENT_KEY_REVOKED,
            subject: $enrollmentKey,
            properties: ['name' => $enrollmentKey->name, 'uses' => $enrollmentKey->uses],
        );

        return AnchorEnrollmentKeyData::fromModel($enrollmentKey->loadMissing('createdBy'));
    }

    /**
     * Housekeeping for a key that can no longer admit anything.
     *
     * Deliberately refuses an active key: allowing it would make deletion a
     * quieter synonym for revocation, and the quieter path is the one that gets
     * taken when someone would rather the incident left no roster entry.
     */
    public function destroy(AnchorEnrollmentKey $enrollmentKey)
    {
        if ($enrollmentKey->status() === EnrollmentKeyStatus::ACTIVE) {
            throw new BadRequestHttpException('Revoke this enrollment key before deleting it.');
        }

        $properties = ['name' => $enrollmentKey->name, 'uses' => $enrollmentKey->uses];

        $enrollmentKey->delete();

        Audit::record(
            AuditEvent::ADMIN_ANCHOR_ENROLLMENT_KEY_DELETED,
            subject: $enrollmentKey,
            properties: $properties,
        );

        return response()->noContent();
    }
}
