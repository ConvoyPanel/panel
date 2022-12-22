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

        try {
            $response = $this->getHttpClient()->post(sprintf('/api2/json/nodes/%s/storage/%s/download-url', $this->node->cluster, $this->node->iso_storage), [
                'json' => [
                    'content' => $contentType->value,
                    'filename' => $fileName,
                    'url' => $link,
                    'checksum' => $checksumData?->checksum,
                    'algorithm' => $checksumData?->algorithm?->value,
                    'verify-certificates' => $verifyCertificates,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function getFileMetadata(string $link, ?bool $verifyCertificates = true)
    {
        Assert::isInstanceOf($this->node, Node::class);
        Assert::regex($link, '/^(http|https):\/\//');

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/query-url-metadata', $this->node->cluster), [
                'query' => [
                    'url' => $link,
                    'verify-certificates' => $verifyCertificates,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

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
