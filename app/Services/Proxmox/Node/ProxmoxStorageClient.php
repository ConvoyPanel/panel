<?php

namespace App\Services\Proxmox\Node;

use App\Data\Helpers\ChecksumData;
use App\Data\Node\Storage\FileMetaData;
use App\Data\Node\Storage\ISOData;
use App\Data\Node\Storage\StorageData;
use App\Enums\Node\Storage\StorageContentType;
use App\Exceptions\Proxmox\RequestException;
use App\Exceptions\Service\Node\ISOLibrary\InvalidISOLinkException;
use App\Services\Proxmox\ProxmoxClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;
use Webmozart\Assert\Assert;

class ProxmoxStorageClient extends ProxmoxClient
{
    /**
     * @return DataCollection<int, StorageData>
     *
     * @throws RequestException
     */
    public function getStorages(): DataCollection
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/storage')
            ->json();

        $response = $this->getData($response);
        $storages = [];
        foreach ($response as $storage) {
            $storages[] = StorageData::fromRaw($storage);
        }

        return StorageData::collect($storages, DataCollection::class);
    }

    public function getStorage(string $name): StorageData
    {
        $response = $this->getHttpClientWithParams([
            'storage' => $name,
        ])
            ->get('/api2/json/nodes/{node}/storage/{storage}/status')
            ->json();

        $response = $this->getData($response);
        $response['storage'] = $name; // Ensure the storage name is included in the response

        return StorageData::fromRaw($response);
    }

    public function download(
        StorageContentType $contentType,
        string $storage,
        string $fileName,
        string $link,
        ?bool $verifyCertificates = true,
        ?ChecksumData $checksumData = null,
    ) {
        Assert::regex($link, '/^(http|https):\/\//', 'Invalid URL provided');

        $payload = [
            'content' => $contentType->toProxmoxString(),
            'filename' => $fileName,
            'url' => $link,
            'verify-certificates' => $verifyCertificates,
        ];

        if ($checksumData) {
            $payload['checksum'] = $checksumData->checksum;
            // PVE calls this `checksum-algorithm`. Sending `algorithm` is not
            // ignored -- the API rejects unknown parameters -- so a download
            // with a checksum failed outright rather than going unverified.
            $payload['checksum-algorithm'] = $checksumData->algorithm->value;
        }

        $response = $this->getHttpClientWithParams([
            'storage' => $storage,
        ])
            ->post('/api2/json/nodes/{node}/storage/{storage}/download-url', $payload)
            ->json();

        return $this->getData($response);
    }

    /**
     * File names PVE currently holds for one content type on a storage.
     *
     * Used to answer "is this image version already here?" without downloading
     * it again. The content listing is the only honest source: a node may have
     * been reinstalled, or the file pruned, since the panel last looked.
     *
     * @return array<int, string>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getFileNames(StorageContentType $contentType, string $storage): array
    {
        $response = $this->getHttpClientWithParams([
            'storage' => $storage,
        ])
            ->get('/api2/json/nodes/{node}/storage/{storage}/content', [
                'content' => $contentType->toProxmoxString(),
            ])
            ->json();

        return collect($this->getData($response))
            ->pluck('volid')
            ->filter()
            // A volid is `storage:content/name`; only the name is comparable.
            ->map(fn (string $volid) => Str::afterLast($volid, '/'))
            ->values()
            ->all();
    }

    public function deleteFile(StorageContentType $contentType, string $storage, string $fileName)
    {
        $response = $this->getHttpClientWithParams([
            'storage' => $storage,
            'file' => "{$storage}:{$contentType->toProxmoxString()}/$fileName",
        ])
            ->delete('/api2/json/nodes/{node}/storage/{storage}/content/{file}')
            ->json();

        return $this->getData($response);
    }

    public function getISOs(string $storage): DataCollection
    {
        $response = $this->getHttpClientWithParams([
            'storage' => $storage,
        ])
            ->get('/api2/json/nodes/{node}/storage/{storage}/content?content=iso')
            ->json();

        $response = $this->getData($response);

        $isos = [];

        foreach ($response as $iso) {
            $isos[] = new ISOData(
                file_name: explode('/', $iso['volid'])[1],
                size     : $iso['size'],
                createdAt: CarbonImmutable::createFromTimestamp($iso['ctime']),
            );
        }

        return ISOData::collect($isos, DataCollection::class);
    }

    /**
     * @throws InvalidISOLinkException
     * @throws ConnectionException
     */
    public function getFileMetadata(string $link, bool $verifyCertificates = true): FileMetaData
    {
        Assert::regex($link, '/^(http|https):\/\//', 'Invalid URL provided');

        try {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/query-url-metadata', [
                    'url' => $link,
                    'verify-certificates' => $verifyCertificates,
                ])
                ->json();
        } catch (RequestException $e) {
            if (str_contains($e->getMessage(), "Can't connect to")) {
                throw new InvalidISOLinkException;
            }

            throw $e;
        }

        if (Arr::get($response, 'success', 1) !== 1) {
            throw new InvalidISOLinkException;
        }

        $data = $this->getData($response);

        return FileMetaData::from([
            'fileName' => $data['filename'],
            'mimeType' => $data['mimetype'],
            'size' => $data['size'],
        ]);
    }
}
