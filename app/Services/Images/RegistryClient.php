<?php

namespace App\Services\Images;

use App\Data\Image\Registry\RegistryCatalogData;
use App\Data\Image\Registry\RegistryGroupData;
use App\Data\Image\Registry\RegistryTemplateData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Reads a published image catalogue.
 *
 * A catalogue is a JSON document at a URL, and that is the whole of the
 * relationship: the panel fetches it when an admin opens the browser, keeps the
 * decoded result for a few minutes so paging around costs one request, and
 * stores nothing. There is no registry entity, no subscription and no sync job,
 * because an import copies an entry into an image definition and the copy is
 * then the panel's own.
 */
class RegistryClient
{
    private const TIMEOUT_SECONDS = 15;

    /** The only schema this panel knows how to read. */
    private const SCHEMA_VERSION = '2';

    public function url(): string
    {
        return (string) config('convoy.registry.url');
    }

    /**
     * @throws ServiceUnavailableHttpException when the catalogue cannot be read
     * @throws UnprocessableEntityHttpException when it is not a catalogue this panel understands
     */
    public function catalog(bool $refresh = false): RegistryCatalogData
    {
        $key = 'images.registry.'.sha1($this->url());

        if ($refresh) {
            Cache::forget($key);
        }

        return Cache::remember(
            $key,
            now()->addMinutes((int) config('convoy.registry.cache_minutes', 30)),
            fn () => $this->decode($this->fetch()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetch(): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get($this->url());
        } catch (ConnectionException $exception) {
            throw new ServiceUnavailableHttpException(
                message: 'Could not reach the image catalogue at '.$this->url().'.',
                previous: $exception,
            );
        }

        if (! $response->successful()) {
            throw new ServiceUnavailableHttpException(
                message: 'The image catalogue at '.$this->url().' answered '.$response->status().'.',
            );
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw new UnprocessableEntityHttpException(
                'The image catalogue at '.$this->url().' is not JSON.',
            );
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function decode(array $raw): RegistryCatalogData
    {
        // Version-checked rather than parsed optimistically: schema 1 published
        // vzdump archives, which have no disks to import and would decode into
        // a catalogue of empty entries instead of an error anyone can act on.
        if ((string) ($raw['schema_version'] ?? '') !== self::SCHEMA_VERSION) {
            throw new UnprocessableEntityHttpException(sprintf(
                'The catalogue at %s is schema %s. This panel reads schema %s.',
                $this->url(),
                $raw['schema_version'] ?? 'unknown',
                self::SCHEMA_VERSION,
            ));
        }

        $groups = collect($raw['groups'] ?? [])
            ->filter(fn ($group) => is_array($group) && filled($group['id'] ?? null))
            ->map(fn (array $group) => new RegistryGroupData(
                slug: (string) $group['id'],
                name: (string) ($group['display_name'] ?? $group['id']),
                description: $group['description'] ?? null,
                templates: RegistryTemplateData::collect(
                    collect($group['templates'] ?? [])
                        ->filter(fn ($template) => is_array($template) && filled($template['name'] ?? null))
                        ->map(fn (array $template) => RegistryTemplateData::fromRegistry($group, $template))
                        ->values()
                        ->all(),
                    DataCollection::class,
                ),
            ))
            ->values()
            ->all();

        return new RegistryCatalogData(
            url: $this->url(),
            name: (string) ($raw['name'] ?? 'Image catalogue'),
            description: $raw['description'] ?? null,
            generatedAt: $raw['generated_at'] ?? null,
            groups: $groups,
        );
    }
}
