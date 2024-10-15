<?php

namespace App\Repositories\Proxmox\Node;

use App\Data\Helpers\ChecksumData;
use App\Data\Node\Storage\FileMetaData;
use App\Data\Node\Storage\IsoData;
use App\Data\Node\Storage\StorageData;
use App\Enums\Node\Storage\ContentType;
use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Exceptions\Service\Node\IsoLibrary\InvalidIsoLinkException;
use App\Models\Node;
use App\Repositories\Proxmox\ProxmoxRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;
use Webmozart\Assert\Assert;

class ProxmoxStorageRepository extends ProxmoxRepository
{
    public function getStorage(string $name): StorageData
    {
        Assert::isInstanceOf($this->node, Node::class);

        $response = $this->getHttpClient()
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'storage' => $name,
                         ])
                         ->get('/api2/json/nodes/{node}/storage/{storage}/status')
                         ->json();

        $response = $this->getData($response);

        $has = fn (string $content) => Str::contains($response['content'], $content);

        return new StorageData(
            name   : $name,
            used   : $response['used'],
            free   : $response['avail'],
            total  : $response['total'],
            enabled: $response['enabled'],
            online : $response['active'],
            has_kvm: $has('images'),
            has_lxc: $has('rootdir'),
            has_lxc_templates: $has('templates'),
            has_backups: $has('backup'),
            has_iso: $has('iso'),
            has_snippets: $has('snippets'),
        );
    }

    public function download(
        ContentType $contentType,
        string $fileName,
        string $link,
        ?bool $verifyCertificates = true,
        ?ChecksumData $checksumData = null,
    ) {
        Assert::isInstanceOf($this->node, Node::class);
        Assert::regex($link, '/^(http|https):\/\//');

        $payload = [
            'content' => $contentType->value,
            'filename' => $fileName,
            'url' => $link,
            'verify-certificates' => $verifyCertificates,
        ];

        if ($checksumData) {
            $payload['checksum'] = $checksumData->checksum;
            $payload['algorithm'] = $checksumData->algorithm->value;
        }

        $response = $this->getHttpClient()
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'storage' => $this->node->iso_storage,
                         ])
                         ->post('/api2/json/nodes/{node}/storage/{storage}/download-url', $payload)
                         ->json();

        return $this->getData($response);
    }

    public function deleteFile(ContentType $contentType, string $fileName)
    {
        Assert::isInstanceOf($this->node, Node::class);

        $response = $this->getHttpClient()
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'storage' => $this->node->iso_storage,
                             'file' => "{$this->node->iso_storage}:$contentType->value/$fileName",
                         ])
                         ->delete('/api2/json/nodes/{node}/storage/{storage}/content/{file}')
                         ->json();

        return $this->getData($response);
    }

    public function getIsos(): DataCollection
    {
        Assert::isInstanceOf($this->node, Node::class);

        $response = $this->getHttpClient()
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'storage' => $this->node->iso_storage,
                         ])
                         ->get('/api2/json/nodes/{node}/storage/{storage}/content?content=iso')
                         ->json();

        $response = $this->getData($response);

        $isos = [];

        foreach ($response as $iso) {
            $isos[] = new IsoData(
                file_name : explode('/', $iso['volid'])[1],
                size      : $iso['size'],
                created_at: CarbonImmutable::createFromTimestamp($iso['ctime']),
            );
        }

        return IsoData::collection($isos);
    }

    public function getFileMetadata(string $link, bool $verifyCertificates = true): FileMetaData
    {
        Assert::isInstanceOf($this->node, Node::class);
        Assert::regex($link, '/^(http|https):\/\//');

        try {
            $response = $this->getHttpClient()
                             ->withUrlParameters([
                                 'node' => $this->node->cluster,
                             ])
                             ->get('/api2/json/nodes/{node}/query-url-metadata', [
                                 'url' => $link,
                                 'verify-certificates' => $verifyCertificates,
                             ])
                             ->json();
        } catch (ProxmoxConnectionException $e) {
            if (str_contains($e->getMessage(), "Can't connect to")) {
                throw new InvalidIsoLinkException();
            }
        }

        if (Arr::get($response, 'success', 1) !== 1) {
            throw new InvalidIsoLinkException();
        }

        $data = $this->getData($response);

        return FileMetaData::from([
            'file_name' => $data['filename'],
            'mime_type' => $data['mimetype'],
            'size' => $data['size'],
        ]);
    }
}
