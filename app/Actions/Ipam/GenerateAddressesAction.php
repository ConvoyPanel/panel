<?php

namespace App\Actions\Ipam;

use App\Models\AddressBlock;
use App\Enums\Network\AddressVersion;
use App\Data\Ipam\GeneratedAddressesData;

class GenerateAddressesAction {
    private const BATCH_SIZE = 300;

    public function execute(AddressBlock $addressBlock): GeneratedAddressesData
    {
        // Sparse blocks (large v4 / any v6) are not pre-materialized — the allocator mints their
        // addresses on demand — so there is nothing to generate here.
        if ($addressBlock->isSparse()) {
            return new GeneratedAddressesData(
                createdCount: 0,
                remaining: 0,
                isComplete: true,
                sparse: true,
            );
        }

        $fromPrefix = $addressBlock->prefix_length_from;
        $toPrefix = $addressBlock->prefix_length_to;
        $baseIp = $addressBlock->base_ip;

        $allAddresses = $addressBlock->version === AddressVersion::IPv4
            ? $this->calculateIPv4Subnets($baseIp, $fromPrefix, $toPrefix)
            : $this->calculateIPv6Subnets($baseIp, $fromPrefix, $toPrefix);

        $existing = $addressBlock->addresses()->pluck('ip')->toArray();
        $toCreate = array_diff($allAddresses, $existing);
        $batchToCreate = array_slice($toCreate, 0, self::BATCH_SIZE);

        // Prepare data for batch insert
        $insertData = [];
        foreach ($batchToCreate as $ip) {
            $insertData[] = [
                'address_block_id' => $addressBlock->id,
                'server_id' => null,
                'ip' => $ip,
                'prefix_length' => $toPrefix,
            ];
        }

        if (!empty($insertData)) {
            $addressBlock->addresses()->insert($insertData);
        }

        $createdCount = count($insertData);
        $remainingCount = count($toCreate) - $createdCount;
        $isComplete = $remainingCount <= 0;

        return new GeneratedAddressesData(
            createdCount: $createdCount,
            remaining: $remainingCount,
            isComplete: $isComplete,
        );
    }

    private function calculateIPv4Subnets(string $baseIp, int $fromPrefix, int $toPrefix): array
    {
        $baseAddr = ip2long($baseIp);
        $baseMask = ~((1 << (32 - $fromPrefix)) - 1);
        $networkAddr = $baseAddr & $baseMask;
        $hostBits = 32 - $toPrefix;
        $subnetIncrement = 1 << $hostBits;
        $numSubnets = 1 << ($toPrefix - $fromPrefix);
        $addresses = [];
        for ($i = 0; $i < $numSubnets; $i++) {
            $subnetAddr = $networkAddr + ($i * $subnetIncrement);
            $addresses[] = long2ip($subnetAddr);
        }
        return $addresses;
    }

    private function calculateIPv6Subnets(string $baseIp, int $fromPrefix, int $toPrefix): array
    {
        $binaryIp = inet_pton($baseIp);
        if ($binaryIp === false) {
            throw new \InvalidArgumentException('Invalid IPv6 address');
        }
        $binaryIp = $this->applyIpv6Mask($binaryIp, $fromPrefix);
        $bitDiff = $toPrefix - $fromPrefix;
        // Dense v6 blocks are bounded by AddressBlock::DENSE_MAX_HOST_BITS (isSparse() already
        // returned early for anything larger), so this loop is capped at 2^16 iterations.
        $numSubnets = 1 << $bitDiff;
        $addresses = [];
        for ($i = 0; $i < $numSubnets; $i++) {
            $newBinaryIp = $binaryIp;
            for ($bit = 0; $bit < $bitDiff; $bit++) {
                $bytePos = intdiv($fromPrefix + $bit, 8);
                $bitPos = ($fromPrefix + $bit) % 8;
                if (($i >> $bit) & 1) {
                    $newBinaryIp[$bytePos] = chr(ord($newBinaryIp[$bytePos]) | (1 << (7 - $bitPos)));
                } else {
                    $newBinaryIp[$bytePos] = chr(ord($newBinaryIp[$bytePos]) & ~(1 << (7 - $bitPos)));
                }
            }
            $addresses[] = inet_ntop($newBinaryIp);
        }
        return $addresses;
    }

    private function applyIpv6Mask(string $binaryIp, int $prefixLength): string
    {
        $result = $binaryIp;
        for ($i = 0; $i < 16; $i++) {
            $bitPos = $i * 8;
            if ($bitPos >= $prefixLength) {
                $result[$i] = chr(0);
            } elseif ($bitPos + 8 > $prefixLength) {
                $bitsToKeep = $prefixLength - $bitPos;
                $mask = ~((1 << (8 - $bitsToKeep)) - 1) & 0xFF;
                $result[$i] = chr(ord($result[$i]) & $mask);
            }
        }
        return $result;
    }
}