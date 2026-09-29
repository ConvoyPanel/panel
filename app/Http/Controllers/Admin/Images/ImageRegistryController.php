<?php

namespace App\Http\Controllers\Admin\Images;

use App\Data\Image\Registry\RegistryImportResultData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\ImageDefinition;
use App\Services\Images\RegistryImportService;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

/**
 * Browses a published image catalogue, and copies entries out of it.
 *
 * A catalogue is read, never subscribed to. Nothing here creates a registry
 * record, and an import leaves behind an ordinary image definition the operator
 * owns -- editable, retirable, deletable -- plus the slug it came from, which
 * is what lets a later fetch say whether the entry has been rebuilt.
 */
class ImageRegistryController
{
    public function __construct(private RegistryImportService $registry) {}

    public function index(Request $request)
    {
        // Flattened here rather than returned as a Data object, which would
        // wrap the groups in a second `data` envelope while the templates
        // inside them stayed plain -- two shapes in one document, for nothing.
        return ['data' => $this->registry->catalog($request->boolean('refresh'))->toArray()];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'templates' => ['required', 'array', 'min:1', 'max:100'],
            'templates.*' => ['required', 'string', 'max:191'],
        ]);

        // Read through the cache the browser was populated from, so importing
        // installs the build the operator was looking at rather than one that
        // happened to be published between the page load and the click.
        $results = $this->registry->importMany($validated['templates']);

        $results
            ->filter(fn (RegistryImportResultData $result) => $result->created)
            ->each(fn (RegistryImportResultData $result) => Audit::record(
                AuditEvent::ADMIN_IMAGE_IMPORTED,
                subject: ImageDefinition::firstWhere('uuid', $result->imageDefinitionUuid),
                properties: ['slug' => $result->slug, 'version' => $result->version],
            ));

        return RegistryImportResultData::collect($results->all(), DataCollection::class);
    }
}
