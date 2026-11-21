<?php

namespace App\Services\Provisioners;

use App\Data\Provisioner\ProvisionerFieldData;
use App\Models\ImageDefinition;
use App\Models\Server;
use Illuminate\Support\Collection;

/**
 * The environment a provisioner script runs with.
 *
 * Every variable is always set, empty when there is nothing to put in it, so a
 * script written with `set -u` does not stop on a server without IPv6.
 */
class ProvisionerEnvironment
{
    /**
     * @param  Collection<int, ProvisionerFieldData>  $fields
     * @param  array<string, string|null>  $values  by field key
     * @return array<string, string>
     */
    public function for(Server $server, ?ImageDefinition $image, Collection $fields, array $values): array
    {
        $ipv4 = $server->primaryIPv4Address;
        $ipv6 = $server->primaryIPv6Address;

        $environment = [
            'CONVOY_SERVER_UUID' => $server->uuid,
            'CONVOY_SERVER_NAME' => $server->name,
            'CONVOY_HOSTNAME' => $server->hostname,
            'CONVOY_IPV4' => (string) $ipv4?->ip,
            'CONVOY_IPV4_GATEWAY' => (string) $ipv4?->gateway,
            'CONVOY_IPV6' => (string) $ipv6?->ip,
            'CONVOY_IPV6_GATEWAY' => (string) $ipv6?->gateway,
            'CONVOY_CPU' => (string) $server->cpu,
            // Bytes, like everywhere else in the panel.
            'CONVOY_MEMORY' => (string) $server->memory,
            'CONVOY_DISK' => (string) $server->disk,
            'CONVOY_IMAGE' => (string) $image?->name,
        ];

        foreach ($fields as $field) {
            $environment[$field->environmentName()] = (string) ($values[$field->key] ?? '');
        }

        return $environment;
    }
}
