<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\Firewall\FirewallLogEntryData;
use App\Data\Server\Proxmox\Firewall\FirewallMacroData;
use App\Data\Server\Proxmox\Firewall\FirewallOptionsData;
use App\Data\Server\Proxmox\Firewall\FirewallRefData;
use App\Data\Server\Proxmox\Firewall\FirewallRuleData;
use App\Data\Server\Proxmox\Network\IpsetData;
use App\Data\Server\Proxmox\Network\LockedIpData;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use IPLib\Factory;
use IPLib\Range\RangeInterface;

class ProxmoxFirewallClient extends ProxmoxClient
{
    /**
     * @throws RequestException
     */
    public function updateOptions(array $payload)
    {
        $response = $this->getHttpClientWithParams()
            ->put('/api2/json/nodes/{node}/qemu/{server}/firewall/options', $payload)
            ->json();

        return $this->getData($response);
    }

    /**
     * The response envelope's `data`, as a list.
     *
     * {@see ProxmoxClient::getData()} falls back to the whole envelope when
     * `data` is absent -- and because it uses `??`, a literal `{"data": null}`
     * takes that branch too. Proxmox returns exactly that for an empty
     * collection, which would otherwise feed the envelope itself into the
     * per-item mappers below and fatal.
     */
    private function getDataList(mixed $response): array
    {
        $data = is_array($response) ? ($response['data'] ?? null) : null;

        return is_array($data) ? $data : [];
    }

    /**
     * @throws RequestException
     */
    public function getOptions(): FirewallOptionsData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/firewall/options')
            ->json();

        return FirewallOptionsData::fromRaw($this->getDataList($response));
    }

    /**
     * Rules in evaluation order. The index is the rule's identity in every
     * other call here, and it renumbers on any insert, delete, or move -- so
     * nothing may be cached against it.
     *
     * @return Collection<int, FirewallRuleData>
     *
     * @throws RequestException
     */
    public function getRules(): Collection
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/firewall/rules')
            ->json();

        return FirewallRuleData::collect(
            Arr::map($this->getDataList($response), fn (array $rule) => FirewallRuleData::fromRaw($rule)),
            Collection::class,
        );
    }

    /**
     * @throws RequestException
     */
    public function createRule(array $payload): void
    {
        $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/firewall/rules', $payload);
    }

    /**
     * @throws RequestException
     */
    public function updateRule(int $position, array $payload): void
    {
        $this->getHttpClientWithParams(['pos' => $position])
            ->put('/api2/json/nodes/{node}/qemu/{server}/firewall/rules/{pos}', $payload);
    }

    /**
     * Deliberately separate from {@see updateRule()}: Proxmox ignores every
     * other argument in a request carrying `moveto`, so folding the two
     * together would silently discard the caller's edits.
     *
     * @throws RequestException
     */
    public function moveRule(int $position, int $newPosition, ?string $digest = null): void
    {
        $payload = ['moveto' => $newPosition];

        if ($digest !== null) {
            $payload['digest'] = $digest;
        }

        $this->getHttpClientWithParams(['pos' => $position])
            ->put('/api2/json/nodes/{node}/qemu/{server}/firewall/rules/{pos}', $payload);
    }

    /**
     * @throws RequestException
     */
    public function deleteRule(int $position, ?string $digest = null): void
    {
        $client = $this->getHttpClientWithParams(['pos' => $position]);

        // The digest goes in the query string, not the body: Proxmox refuses a
        // DELETE carrying content outright ("Unexpected content for method
        // 'DELETE'"), regardless of what the content actually is.
        if ($digest !== null) {
            $client->withQueryParameters(['digest' => $digest]);
        }

        $client->delete('/api2/json/nodes/{node}/qemu/{server}/firewall/rules/{pos}');
    }

    /**
     * Aliases and IP sets nameable in a rule's source or destination, merged
     * across this server's own config and the datacenter's.
     *
     * @return Collection<int, FirewallRefData>
     *
     * @throws RequestException
     */
    public function getRefs(): Collection
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/firewall/refs')
            ->json();

        return FirewallRefData::collect(
            Arr::map($this->getDataList($response), fn (array $ref) => FirewallRefData::fromRaw($ref)),
            Collection::class,
        );
    }

    /**
     * @return Collection<int, FirewallLogEntryData>
     *
     * @throws RequestException
     */
    public function getLog(int $start = 0, int $limit = 100): Collection
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/firewall/log', [
                'start' => $start,
                'limit' => $limit,
            ])
            ->json();

        return FirewallLogEntryData::collect(
            Arr::map($this->getDataList($response), fn (array $line) => FirewallLogEntryData::fromRaw($line)),
            Collection::class,
        );
    }

    /**
     * The cluster's predefined traffic macros. Cluster-scoped rather than
     * node-scoped, but it answers on any node, and the base URL is already
     * pointed at one.
     *
     * @return Collection<int, FirewallMacroData>
     *
     * @throws RequestException
     */
    public function getMacros(): Collection
    {
        $response = $this->getHttpClient()
            ->get('/api2/json/cluster/firewall/macros')
            ->json();

        return FirewallMacroData::collect(
            Arr::map($this->getDataList($response), fn (array $macro) => FirewallMacroData::fromRaw($macro)),
            Collection::class,
        );
    }

    /**
     * @return Collection<int, IpsetData>
     *
     * @throws RequestException
     */
    public function getIpsets(): Collection
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/firewall/ipset')
            ->json();

        return IpsetData::collect(Arr::map($this->getData($response), function (array $item) {
            /** @var array{name: string, digest: string, comment?: string|null} $item */

            return [
                'name' => $item['name'],
                'comment' => $item['comment'] ?? null,
            ];
        }), Collection::class);
    }

    /**
     * @throws RequestException
     */
    public function createIpset(string $name, string $comments = 'Generated by Convoy'): void
    {
        $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/firewall/ipset', [
                'name' => $name,
                'comment' => $comments,
            ]);
    }

    /**
     * @throws RequestException
     */
    public function deleteIpset(string|IpsetData $ipset): void
    {
        if ($ipset instanceof IpsetData) {
            $ipset = $ipset->name;
        }

        $this->getHttpClientWithParams([
            'ipset' => $ipset,
        ])
            ->delete('/api2/json/nodes/{node}/qemu/{server}/firewall/ipset/{ipset}');
    }

    /**
     * @return Collection<int, LockedIpData>
     *
     * @throws RequestException
     */
    public function getLockedIps(string|IpsetData $ipset): Collection
    {
        if ($ipset instanceof IpsetData) {
            $ipset = $ipset->name;
        }

        $response = $this->getHttpClientWithParams([
            'ipset' => $ipset,
        ])
            ->get('/api2/json/nodes/{node}/qemu/{server}/firewall/ipset/{ipset}')
            ->json();

        return LockedIpData::collect(Arr::map($this->getData($response), function (array $item) {
            /** @var array{ cidr: string, comment: ?string, digest: string} $item */

            return [
                'ip' => Factory::parseRangeString($item['cidr']),
                'comment' => $item['comment'] ?? null,
                'originalIp' => $item['cidr'],
            ];
        }), Collection::class);
    }

    /**
     * @throws RequestException
     */
    public function lockIp(string|IpsetData $ipset, string|RangeInterface $ip, string $comments = 'Generated by Convoy'): void
    {
        if ($ipset instanceof IpsetData) {
            $ipset = $ipset->name;
        }

        if ($ip instanceof RangeInterface) {
            $ip = $ip->toString();
        }

        $this->getHttpClientWithParams([
            'ipset' => $ipset,
        ])
            ->post('/api2/json/nodes/{node}/qemu/{server}/firewall/ipset/{ipset}', [
                'cidr' => $ip,
                'nomatch' => false,
                'comment' => $comments,
            ]);
    }

    /**
     * @throws RequestException
     */
    public function unlockIp(string|IpsetData $ipset, string|RangeInterface|LockedIpData $ip): void
    {
        if ($ipset instanceof IpsetData) {
            $ipset = $ipset->name;
        }

        if ($ip instanceof RangeInterface) {
            $ip = $ip->toString();
        }

        if ($ip instanceof LockedIpData) {
            $ip = $ip->originalIp;
        }

        $this->getHttpClientWithParams([
            'ipset' => $ipset,
            'address' => $ip,
        ])
            ->delete('/api2/json/nodes/{node}/qemu/{server}/firewall/ipset/{ipset}/{address}');
    }
}
