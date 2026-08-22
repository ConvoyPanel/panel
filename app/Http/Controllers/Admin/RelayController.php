<?php

namespace App\Http\Controllers\Admin;

use App\Data\Anchor\RelayData;
use App\Data\PaginationMeta;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\RelayFormRequest;
use App\Models\Relay;
use App\Services\Anchor\AnchorEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RelayController
{
    public function index(Request $request)
    {
        $relays = QueryBuilder::for(Relay::query())
            ->withCount('nodes')
            ->defaultSort('name')
            ->allowedFilters(['name'])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($relays, RelayData::class);
    }

    public function show(Relay $relay)
    {
        return RelayData::from($relay->loadCount('nodes'));
    }

    public function store(RelayFormRequest $request)
    {
        $relay = Relay::create([
            ...$request->validated(),
            'uuid' => (string) Str::uuid(),
            'secret' => Str::random(64),
        ]);

        // The generated secret is never recorded -- it is a live credential.
        Audit::record(
            AuditEvent::ADMIN_RELAY_CREATED,
            subject: $relay,
            properties: ['name' => $relay->name],
        );

        return RelayData::from($relay->loadCount('nodes'));
    }

    public function update(RelayFormRequest $request, Relay $relay)
    {
        $relay->update($request->validated());

        Audit::record(
            AuditEvent::ADMIN_RELAY_UPDATED,
            subject: $relay,
            properties: ['name' => $relay->name, 'changed' => array_keys($relay->getChanges())],
        );

        return RelayData::from($relay->loadCount('nodes'));
    }

    public function enrollment(Relay $relay, AnchorEnrollmentService $enrollment)
    {
        $details = $enrollment->issue($relay);

        // Enrolling rotates the secret, so this both grants access and revokes
        // the previous one. The issued secret itself is never recorded.
        Audit::record(
            AuditEvent::ADMIN_ANCHOR_ENROLLMENT_ROTATED,
            subject: $relay,
            properties: ['name' => $relay->name],
        );

        return $details;
    }

    public function destroy(Relay $relay)
    {
        $relay->loadCount('nodes');

        if ($relay->nodes_count > 0) {
            throw new BadRequestHttpException('Move these nodes off this relay before deleting it.');
        }

        $name = $relay->name;

        $relay->delete();

        Audit::record(AuditEvent::ADMIN_RELAY_DELETED, subject: $relay, properties: ['name' => $name]);

        return response()->noContent();
    }
}
