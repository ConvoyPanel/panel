<?php

namespace Convoy\Repositories\Proxmox\Node;

use Convoy\Models\Node;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;
use Convoy\Data\Helpers\ChecksumData;
use Convoy\Data\Node\Storage\IsoData;
use Spatie\LaravelData\DataCollection;
use Convoy\Data\Node\Storage\FileMetaData;
use Convoy\Enums\Node\Storage\ContentType;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use Convoy\Exceptions\Service\Node\IsoLibrary\InvalidIsoLinkException;

class ProxmoxStorageRepository extends ProxmoxRepository
{
    public function download(ContentType $contentType, string $fileName, string $link, ?bool $verifyCertificates = true, ?ChecksumData $checksumData = null)
    {
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

    public function getIsos()
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
            $isos[] = IsoData::from([
                'file_name' => explode('/', $iso['volid'])[1],
                'size' => $iso['size'],
                'created_at' => CarbonImmutable::createFromTimestamp($iso['ctime']),
            ]);
        }

        return IsoData::collection($isos);
    }

    public function getFileMetadata(string $link, ?bool $verifyCertificates = true): FileMetaData
    {
        Assert::isInstanceOf($this->node, Node::class);
        Assert::regex($link, '/^(http|https):\/\//');

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
            ])
            ->get('/api2/json/nodes/{node}/query-url-metadata', [
                'url' => $link,
                'verify-certificates' => $verifyCertificates,
            ])
            ->json();

        if (Arr::get($response, 'success', 1) !== 1) {
            throw new InvalidIsoLinkException;
        }

        $data = $this->getData($response);

        return FileMetaData::from([
            'file_name' => $data['filename'],
            'mime_type' => $data['mimetype'],
            'size' => $data['size'],
        ]);
    }
}
