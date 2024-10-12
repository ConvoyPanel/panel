<?php

namespace Convoy\Repositories\Eloquent;

use Convoy\Models\Server;
use Illuminate\Support\Facades\DB;

class AddressRepository
{
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
