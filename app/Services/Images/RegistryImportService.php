<?php

namespace App\Services\Images;

use App\Data\Image\ImageDiskData;
use App\Data\Image\Registry\RegistryCatalogData;
use App\Data\Image\Registry\RegistryGroupData;
use App\Data\Image\Registry\RegistryImportResultData;
use App\Data\Image\Registry\RegistryTemplateData;
use App\Enums\Image\ImageSource;
use App\Enums\Image\RegistryImportStatus;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\ImageVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Copies a catalogue entry into an image definition.
 *
 * Importing is a copy, not a link: what the panel keeps afterwards is an
 * ordinary definition and version an operator can edit, retire and delete, plus
 * the slug it came from. The slug is the whole of the relationship, and it buys
 * two things -- a second import recognises what it already made, and the
 * catalogue can be re-read to see whether an entry has been rebuilt since.
 *
 * A rebuild is a new version, never a second definition. The hardware profile
 * and the bytes have different lifecycles, which is the same reason the two
 * are separate tables at all: an operator's plan floors, admin-only flag and
 * name survive every rebuild of the image they describe.
 */
class RegistryImportService
{
    public function __construct(private RegistryClient $client) {}

    /**
     * The catalogue, with what the panel already holds marked on each entry.
     *
     * Computed per fetch by comparing published disk hashes against imported
     * versions, so no import state is stored and none can go stale.
     */
    public function catalog(bool $refresh = false): RegistryCatalogData
    {
        $catalog = $this->client->catalog($refresh);

        $definitions = ImageDefinition::query()
            ->whereNotNull('registry_slug')
            ->with('versions')
            ->get()
            ->keyBy('registry_slug');

        collect($catalog->groups)->each(
            fn (RegistryGroupData $group) => $group->templates->toCollection()->each(
                fn (RegistryTemplateData $template) => $this->markStatus(
                    $template,
                    $definitions->get($template->slug),
                ),
            ),
        );

        return $catalog;
    }

    /**
     * @param  array<int, string>  $slugs
     * @return Collection<int, RegistryImportResultData>
     */
    public function importMany(array $slugs, bool $refresh = false): Collection
    {
        $catalog = $this->client->catalog($refresh);

        return collect($slugs)
            ->unique()
            ->values()
            ->map(fn (string $slug) => $this->import($this->find($catalog, $slug)));
    }

    /**
     * Idempotent by content: the same build imported twice adds nothing.
     *
     * Identity is the set of disk hashes rather than the derived version
     * number, because the catalogue publishes no version at all -- the number
     * comes from the build date, and two builds on one day would otherwise be
     * indistinguishable. Hashes make "the panel already has this build" a fact
     * about the bytes rather than about a label.
     */
    public function import(RegistryTemplateData $template): RegistryImportResultData
    {
        return DB::transaction(function () use ($template) {
            $group = $this->groupFor($template);
            $definition = $this->definitionFor($template, $group);
            $existing = $this->versionHolding($definition, $template);

            if ($existing) {
                return new RegistryImportResultData(
                    slug: $template->slug,
                    imageGroupUuid: $group->uuid,
                    imageDefinitionUuid: $definition->uuid,
                    version: $existing->version,
                    created: false,
                );
            }

            $version = $definition->versions()->create([
                'version' => $this->freeVersionNumber($definition, $template->version),
                'source' => ImageSource::REGISTRY->value,
                // Written out by hand rather than with `toArray()`: the disk
                // DTO maps its input from snake_case, so a camelCased blob
                // would round-trip back with `virtual_size` missing and the
                // plan floor silently at zero.
                'disks' => $template->disks->toCollection()
                    ->map(fn (ImageDiskData $disk) => [
                        'slot' => $disk->slot,
                        'role' => $disk->role->value,
                        'url' => $disk->url,
                        'path' => $disk->path,
                        'sha256' => $disk->sha256,
                        'size' => $disk->size,
                        'virtual_size' => $disk->virtualSize,
                        'format' => $disk->format,
                        'options' => $disk->options,
                    ])
                    ->all(),
                'is_active' => true,
            ]);

            return new RegistryImportResultData(
                slug: $template->slug,
                imageGroupUuid: $group->uuid,
                imageDefinitionUuid: $definition->uuid,
                version: $version->version,
                created: true,
            );
        });
    }

    public function find(RegistryCatalogData $catalog, string $slug): RegistryTemplateData
    {
        $template = collect($catalog->groups)
            ->flatMap(fn (RegistryGroupData $group) => $group->templates->toCollection())
            ->first(fn (RegistryTemplateData $template) => $template->slug === $slug);

        return $template ?? throw new NotFoundHttpException(
            "The catalogue has no template called `{$slug}`.",
        );
    }

    private function markStatus(RegistryTemplateData $template, ?ImageDefinition $definition): void
    {
        if (is_null($definition)) {
            return;
        }

        $held = $this->versionHolding($definition, $template);

        if ($held) {
            $template->status = RegistryImportStatus::IMPORTED;
            $template->importedVersion = $held->version;

            return;
        }

        $template->status = RegistryImportStatus::UPDATE_AVAILABLE;
        $template->importedVersion = $definition->latestVersion()?->version;
    }

    /**
     * The version, if any, whose disks are exactly this build's.
     */
    private function versionHolding(ImageDefinition $definition, RegistryTemplateData $template): ?ImageVersion
    {
        $wanted = $template->diskHashes();

        return $definition->versions
            ->first(function (ImageVersion $version) use ($wanted) {
                $hashes = $version->diskSet()->map(fn (ImageDiskData $disk) => $disk->sha256)->all();
                sort($hashes);

                return $hashes === $wanted;
            });
    }

    private function groupFor(RegistryTemplateData $template): ImageGroup
    {
        $group = ImageGroup::firstWhere('registry_slug', $template->groupSlug);

        if ($group) {
            return $group;
        }

        // An operator who already made a group by this name gets their group
        // rather than a second one beside it with the same label.
        $adopted = ImageGroup::whereNull('registry_slug')
            ->where('name', $template->groupName)
            ->first();

        if ($adopted) {
            $adopted->update(['registry_slug' => $template->groupSlug]);

            return $adopted;
        }

        return ImageGroup::create([
            'registry_slug' => $template->groupSlug,
            'name' => $template->groupName,
            'icon' => $this->iconFor($template->groupSlug),
        ]);
    }

    private function definitionFor(RegistryTemplateData $template, ImageGroup $group): ImageDefinition
    {
        $definition = ImageDefinition::with('versions')->firstWhere('registry_slug', $template->slug);

        if ($definition) {
            // The catalogue is authoritative for what it publishes -- the
            // profile and the floors were captured from the machine that was
            // actually built. It is not authoritative for what the operator
            // decided: the name they gave it, its description, and whether
            // tenants may see it are left exactly as they are.
            $definition->update([
                'ostype' => $template->ostype,
                'hardware' => $template->hardware,
                'minimum_cores' => $template->minimumCores,
                'minimum_memory' => $template->minimumMemory,
            ]);

            return $definition->load('versions');
        }

        $sameName = ImageDefinition::where('image_group_id', $group->id)
            ->where('name', $template->display)
            ->first();

        if ($sameName && filled($sameName->registry_slug)) {
            throw new ConflictHttpException(sprintf(
                '`%s` in %s was already imported from `%s`. Rename one of them first.',
                $template->display,
                $group->name,
                $sameName->registry_slug,
            ));
        }

        if ($sameName) {
            $sameName->update([
                'registry_slug' => $template->slug,
                'ostype' => $template->ostype,
                'hardware' => $template->hardware,
                'minimum_cores' => $template->minimumCores,
                'minimum_memory' => $template->minimumMemory,
            ]);

            return $sameName->load('versions');
        }

        $definition = $group->definitions()->create([
            'registry_slug' => $template->slug,
            'name' => $template->display,
            'description' => $template->description,
            'ostype' => $template->ostype,
            'hardware' => $template->hardware,
            'minimum_cores' => $template->minimumCores,
            'minimum_memory' => $template->minimumMemory,
        ]);

        return $definition->load('versions');
    }

    /**
     * The build's own number, or the next free patch beside it.
     *
     * The number comes from the build date, so two builds published on one day
     * collide on a column that is unique per definition. Only reached when the
     * disks differ, which is checked first -- so a bump always means a
     * genuinely different build, never the same one arriving twice.
     */
    private function freeVersionNumber(ImageDefinition $definition, string $version): string
    {
        $taken = $definition->versions()->pluck('version')->all();

        if (! in_array($version, $taken, true)) {
            return $version;
        }

        [$major, $minor, $patch] = array_pad(array_map('intval', explode('.', $version)), 3, 0);

        do {
            $patch++;
            $candidate = "{$major}.{$minor}.{$patch}";
        } while (in_array($candidate, $taken, true));

        return $candidate;
    }

    /**
     * The picker's icon for a group the import created.
     *
     * A guess, and only ever applied to a group the panel is creating for the
     * first time -- an operator's own choice is never overwritten.
     */
    private function iconFor(string $slug): ?string
    {
        $normalized = str_replace('-', '_', $slug);

        return match (true) {
            str_starts_with($normalized, 'windows') => 'windows',
            str_starts_with($normalized, 'rocky') => 'rocky_linux',
            str_starts_with($normalized, 'alma') => 'almalinux',
            str_starts_with($normalized, 'ubuntu') => 'ubuntu',
            str_starts_with($normalized, 'debian') => 'debian',
            str_starts_with($normalized, 'centos') => 'centos',
            str_starts_with($normalized, 'fedora') => 'fedora',
            str_starts_with($normalized, 'alpine') => 'alpine_linux',
            str_starts_with($normalized, 'arch') => 'arch_linux',
            default => null,
        };
    }
}
