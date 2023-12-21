<?php

namespace Convoy\Repositories\Eloquent;

use Convoy\Enums\Network\AddressType;
use Convoy\Models\Address;
use Convoy\Models\Server;
use Illuminate\Support\Facades\DB;

class AddressRepository extends EloquentRepository
{
    public function model(): string
    {
        return Address::class;
    }

    public function bulkCreateIPv4Addresses(
        string  $startingAddress,
        string  $endingAddress,
        int     $addressPoolId,
        ?int    $serverId,
        int     $cidr,
        string  $gateway,
        ?string $macAddress,
    ): void
    {
        $query = <<<'SQL'
        INSERT INTO ip_addresses (address_pool_id, server_id, type, address, cidr, gateway, mac_address, created_at)
        WITH RECURSIVE ips_to_insert AS (
            SELECT INET_NTOA(INET_ATON(:starting_address)) AS ip
            UNION ALL
            SELECT INET_NTOA(INET_ATON(ip) + 1)
            FROM ips_to_insert
            WHERE INET_ATON(ip) < INET_ATON(:ending_address)
        )
        SELECT
            :address_pool_id AS address_pool_id,
            :server_id AS server_id,
            :type AS type,
            r.ip AS address,
            :cidr AS cidr,
            :gateway AS gateway,
            :mac_address AS mac_address,
            NOW() as created_at
        FROM ips_to_insert AS r
        LEFT JOIN ip_addresses AS existing_ips_in_pool
            ON r.ip = existing_ips_in_pool.address
            AND existing_ips_in_pool.address_pool_id = :address_pool_id_2
        WHERE existing_ips_in_pool.address IS NULL;
        SQL;

        DB::select(
            $query,
            [
                'address_pool_id' => $addressPoolId,
                'address_pool_id_2' => $addressPoolId,
                'server_id' => $serverId,
                'type' => AddressType::IPV4->value,
                'starting_address' => $startingAddress,
                'ending_address' => $endingAddress,
                'cidr' => $cidr,
                'gateway' => $gateway,
                'mac_address' => $macAddress,
            ],
        );
    }

    public function bulkCreateIPv6Addresses(
        string  $startingAddress,
        string  $endingAddress,
        int     $addressPoolId,
        ?int    $serverId,
        int     $cidr,
        string  $gateway,
        ?string $macAddress,
    ): void
    {
        $query = <<<'SQL'
        INSERT INTO ip_addresses (address_pool_id, server_id, type, address, cidr, gateway, mac_address, created_at)
        WITH RECURSIVE ips_to_insert AS (
            SELECT INET6_NTOA(INET6_ATON(:starting_address)) AS ip
            UNION ALL
            SELECT INET6_NTOA(INE6T_ATON(ip) + 1)
            FROM ips_to_insert
            WHERE INET6_ATON(ip) < INET6_ATON(:ending_address)
        )
        SELECT
            :address_pool_id AS address_pool_id,
            :server_id AS server_id,
            :type AS type,
            r.ip AS address,
            :cidr AS cidr,
            :gateway AS gateway,
            :mac_address AS mac_address,
            NOW() as created_at
        FROM ips_to_insert AS r
        LEFT JOIN ip_addresses AS existing_ips_in_pool
            ON r.ip = existing_ips_in_pool.address
            AND existing_ips_in_pool.address_pool_id = :address_pool_id_2
        WHERE existing_ips_in_pool.address IS NULL;
        SQL;

        DB::select(
            $query,
            [
                'address_pool_id' => $addressPoolId,
                'address_pool_id_2' => $addressPoolId,
                'server_id' => $serverId,
                'type' => AddressType::IPV4->value,
                'starting_address' => $startingAddress,
                'ending_address' => $endingAddress,
                'cidr' => $cidr,
                'gateway' => $gateway,
                'mac_address' => $macAddress,
            ],
        );
    }

    /**
     * @param Server $server
     * @param int[] $addressIds
     * @return void
     */
    public function attachAddresses(Server $server, array $addressIds): void
    {
        $placeholders = implode(',', array_fill(0, count($addressIds), '?'));


        $query = <<<'SQL'
            UPDATE ip_addresses
            SET server_id = ?
        SQL;

        $query .= " WHERE id IN ({$placeholders})";

        $query .= <<<'SQL'
            AND server_id IS NULL
            AND address_pool_id IN (
                SELECT ap.id
                FROM address_pools ap
                JOIN address_pool_to_node apn ON ap.id = apn.address_pool_id
                WHERE apn.node_id = ?
            );
        SQL;

        DB::select($query, [
            $server->id,
            ...$addressIds,
            $server->node_id,
        ]);
    }
}