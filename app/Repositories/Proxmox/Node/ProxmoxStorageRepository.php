<?php

namespace Convoy\Repositories\Proxmox\Node;

use Convoy\Data\Helpers\ChecksumData;
use Convoy\Data\Node\Storage\FileMetaData;
use Convoy\Enums\Node\Storage\ContentType;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Exceptions\Service\Node\IsoLibrary\InvalidIsoLinkException;
use Convoy\Models\Node;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

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
                'file' => "{$this->node->iso_storage}:$contentType->value/$fileName"
            ])
            ->delete('/api2/json/nodes/{node}/storage/{storage}/content//{file}')
            ->json();

        return $this->getData($response);
    }

    public function getFileMetadata(string $link, ?bool $verifyCertificates = true)
    {
        Assert::isInstanceOf($this->node, Node::class);
        Assert::regex($link, '/^(http|https):\/\//');

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
            ])
            ->get('/api2/json/nodes/%s/query-url-metadata', [
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
