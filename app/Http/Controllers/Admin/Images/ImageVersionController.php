<?php

namespace App\Http\Controllers\Admin\Images;

use App\Data\Image\ImageVersionData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Images\ImageVersionRequest;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\ImageVersion;
use Illuminate\Http\Response;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ImageVersionController
{
    public function index(ImageGroup $imageGroup, ImageDefinition $imageDefinition)
    {
        $versions = $imageDefinition->versions()
            ->orderByDesc('version_major')
            ->orderByDesc('version_minor')
            ->orderByDesc('version_patch')
            ->get();

        return ImageVersionData::collect($versions, DataCollection::class);
    }

    public function store(
        ImageVersionRequest $request,
        ImageGroup $imageGroup,
        ImageDefinition $imageDefinition,
    ) {
        $version = $imageDefinition->versions()->create($request->validated());

        Audit::record(
            AuditEvent::ADMIN_IMAGE_VERSION_CREATED,
            subject: $version,
            properties: ['image' => $imageDefinition->name, 'version' => $version->version],
        );

        return ImageVersionData::from($version);
    }

    public function show(ImageGroup $imageGroup, ImageDefinition $imageDefinition, ImageVersion $imageVersion)
    {
        return ImageVersionData::from($imageVersion);
    }

    /**
     * Only `is_active` is editable.
     *
     * A version's disks are what a server was built from; rewriting them would
     * silently change the answer to "where did this machine come from" for
     * every server already pointing here. A corrected build is a new version.
     */
    public function update(
        ImageVersionRequest $request,
        ImageGroup $imageGroup,
        ImageDefinition $imageDefinition,
        ImageVersion $imageVersion,
    ) {
        $imageVersion->update($request->safe()->only('is_active'));

        Audit::record(
            AuditEvent::ADMIN_IMAGE_VERSION_UPDATED,
            subject: $imageVersion,
            properties: ['version' => $imageVersion->version, 'is_active' => $imageVersion->is_active],
        );

        return ImageVersionData::from($imageVersion);
    }

    public function destroy(
        ImageGroup $imageGroup,
        ImageDefinition $imageDefinition,
        ImageVersion $imageVersion,
    ): Response {
        // Deployments reference the version they built from, and that record is
        // the only place a server's provenance lives. Retiring hides it from
        // the picker without destroying the history.
        if ($imageVersion->deployments()->exists()) {
            throw new ConflictHttpException(
                'Servers were built from this version. Retire it instead of deleting it.',
            );
        }

        $number = $imageVersion->version;

        $imageVersion->delete();

        Audit::record(
            AuditEvent::ADMIN_IMAGE_VERSION_DELETED,
            subject: $imageVersion,
            properties: ['image' => $imageDefinition->name, 'version' => $number],
        );

        return response()->noContent();
    }
}
