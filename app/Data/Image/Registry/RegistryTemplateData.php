<?php

namespace App\Data\Image\Registry;

use App\Data\Image\ImageDiskData;
use App\Enums\Image\ImageDiskRole;
use App\Enums\Image\RegistryImportStatus;
use App\Services\Images\OsProfiles;
use App\Rules\ValidHardwareProfile;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * One entry in a published catalogue, in the panel's own terms.
 *
 * The catalogue's wire format is not this model's problem past this class: a
 * template arrives as a group of disks, a captured `qm config` and a floor, and
 * leaves as the three things an import needs -- disks the panel can already
 * store, an `ostype`, and a hardware overlay. Everything the panel cannot use
 * is dropped here rather than carried around and stripped later.
 */
class RegistryTemplateData extends Data
{
    /**
     * Catalogue keys that are not `qm create` parameters.
     *
     * The catalogue documents its hardware block as mapping 1:1 onto `qm
     * create` with one exception, `net_model`, which names only the NIC model
     * because the bridge and MAC belong to whoever builds the guest. `tpm` is a
     * second: a TPM is allocated as a `tpmstate0` volume, not a `tpm` setting,
     * so forwarding it verbatim would fail the create call.
     */
    private const NOT_PROXMOX_KEYS = ['net_model', 'tpm'];

    public function __construct(
        /** The catalogue's own name for this entry, e.g. `ubuntu-24.04-amd64`. */
        public string $slug,
        public string $groupSlug,
        public string $groupName,
        public string $display,
        public string $arch,
        public string $ostype,
        public ?string $description,
        /** Derived from the build date: the catalogue numbers nothing itself. */
        public string $version,
        public string $builtAt,
        /** @var DataCollection<int, ImageDiskData> */
        public DataCollection $disks,
        /** The overlay an imported definition gets, already stripped. */
        public array $hardware,
        /** Total bytes a node transfers for this entry. */
        public int $size,
        /** The provisioned size of the system disk: the smallest plan that fits. */
        public int $minimumDisk,
        public ?int $minimumCores,
        public ?int $minimumMemory,
        public RegistryImportStatus $status = RegistryImportStatus::NEW,
        /** The version number the panel imported this entry as, if it did. */
        public ?string $importedVersion = null,
    ) {}

    /**
     * @param  array<string, mixed>  $group
     * @param  array<string, mixed>  $template
     */
    public static function fromRegistry(array $group, array $template): self
    {
        $disks = collect(Arr::get($template, 'disks', []))
            ->map(fn (array $disk) => self::diskFrom($disk))
            ->values();

        $system = $disks->first(fn (ImageDiskData $disk) => $disk->isSystem());
        $ostype = (string) Arr::get($template, 'hardware.ostype', 'l26');

        return new self(
            slug: (string) $template['name'],
            groupSlug: (string) $group['id'],
            groupName: (string) ($group['display_name'] ?? $group['id']),
            display: (string) ($template['display'] ?? $template['name']),
            arch: (string) ($template['arch'] ?? 'amd64'),
            ostype: $ostype,
            description: Arr::get($template, 'description'),
            version: self::versionFrom((string) ($template['built_at'] ?? '')),
            builtAt: (string) ($template['built_at'] ?? ''),
            disks: ImageDiskData::collect($disks->all(), DataCollection::class),
            hardware: self::hardwareFrom(Arr::get($template, 'hardware', []), $system),
            size: (int) $disks->sum(fn (ImageDiskData $disk) => $disk->size),
            minimumDisk: $system?->virtualSize ?? 0,
            minimumCores: Arr::get($template, 'minimum.cores'),
            minimumMemory: Arr::get($template, 'minimum.memory'),
        );
    }

    /**
     * The disks, hashes and sizes this entry is identified by.
     *
     * Content rather than a version number, because the catalogue publishes no
     * version number: two fetches describing the same bytes are the same build,
     * and a rebuild is a different one.
     *
     * @return array<int, string>
     */
    public function diskHashes(): array
    {
        $hashes = $this->disks->toCollection()
            ->map(fn (ImageDiskData $disk) => $disk->sha256)
            ->all();

        sort($hashes);

        return $hashes;
    }

    /**
     * @param  array<string, mixed>  $disk
     */
    private static function diskFrom(array $disk): ImageDiskData
    {
        $size = (int) ($disk['size'] ?? 0);

        return new ImageDiskData(
            slot: (string) ($disk['slot'] ?? 'scsi0'),
            role: ImageDiskRole::from((string) ($disk['role'] ?? 'system')),
            // A catalogue hosts its own files, so an import is a link and never
            // an upload: the hash is already published and the bytes never
            // pass through the panel.
            url: (string) ($disk['url'] ?? ''),
            path: null,
            sha256: (string) ($disk['sha256'] ?? ''),
            size: $size,
            // Only the system disk declares one. A varstore is a raw file that
            // is its own provisioned size.
            virtualSize: self::bytesFrom(Arr::get($disk, 'virtual_size')) ?? $size,
            format: (string) ($disk['format'] ?? 'qcow2'),
            options: collect(Arr::get($disk, 'options', []))
                ->filter(fn ($value) => is_string($value) || is_int($value))
                ->all(),
        );
    }

    /**
     * The hardware overlay an imported definition gets.
     *
     * Kept verbatim apart from three removals, because the catalogue captures
     * it from the machine it actually built and booted rather than writing it
     * by hand: `ostype` has its own column, the keys the panel composes at
     * build time would silently overwrite the disk being imported into, and the
     * catalogue's own non-Proxmox keys would fail the create call.
     *
     * @param  array<string, mixed>  $hardware
     * @return array<string, mixed>
     */
    private static function hardwareFrom(array $hardware, ?ImageDiskData $system): array
    {
        $overlay = collect($hardware)
            ->reject(fn ($value, string $key) => $key === 'ostype'
                || in_array($key, self::NOT_PROXMOX_KEYS, true)
                || in_array($key, ValidHardwareProfile::COMPUTED_KEYS, true)
                || preg_match(ValidHardwareProfile::COMPUTED_PATTERN, $key))
            ->all();

        // Only when it differs from what the OS profile already says, so the
        // admin form shows an inherited value as inherited rather than as
        // something the import decided.
        $ostype = (string) ($hardware['ostype'] ?? 'l26');
        $slot = $system?->slot;

        if (filled($slot) && $slot !== (OsProfiles::defaults($ostype)['boot_disk_slot'] ?? null)) {
            $overlay['boot_disk_slot'] = $slot;
        }

        return $overlay;
    }

    /**
     * A version number from the build date, because the catalogue has none.
     *
     * `2026.9.4` rather than a counter: it is the one fact about a build that
     * is already published, it orders the way the version triple does, and two
     * panels importing the same entry agree on it without coordinating.
     */
    private static function versionFrom(string $builtAt): string
    {
        $timestamp = strtotime($builtAt);

        if ($timestamp === false) {
            return '0.0.0';
        }

        return date('Y', $timestamp).'.'.(int) date('n', $timestamp).'.'.(int) date('j', $timestamp);
    }

    /**
     * `32G` as the catalogue writes it, in bytes.
     *
     * Binary units, matching `qemu-img`: a `32G` image is 32 GiB of provisioned
     * space, and reading it as 32 * 10^9 would put the plan floor below the
     * disk that actually arrives.
     */
    private static function bytesFrom(mixed $value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value > 0 ? (int) $value : null;
        }

        if (! is_string($value) || ! preg_match('/^\s*([\d.]+)\s*([KMGTP])?i?B?\s*$/i', $value, $matches)) {
            return null;
        }

        $multiplier = match (strtoupper($matches[2] ?? '')) {
            'K' => 1024 ** 1,
            'M' => 1024 ** 2,
            'G' => 1024 ** 3,
            'T' => 1024 ** 4,
            'P' => 1024 ** 5,
            default => 1,
        };

        $bytes = (int) round(((float) $matches[1]) * $multiplier);

        return $bytes > 0 ? $bytes : null;
    }
}
