<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            $table->dropForeign(['node_id']);
        });

        Schema::rename('ip_addresses', 'temp_ip_addresses');

        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('address_pool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address');
            $table->string('cidr');
            $table->string('gateway');
            $table->string('mac_address')->nullable();
            $table->string('type');
            $table->timestamps();
        });

        /**
         * The key is the node id and the value is the address pool id
         * @var array<int, int> $pools
         */
        $pools = [];

        DB::table('temp_ip_addresses')->join('nodes', 'temp_ip_addresses.node_id', '=', 'nodes.id')->select('temp_ip_addresses.*', 'nodes.fqdn')->orderBy('id')->chunk(100, function (Collection $addresses) use ($pools) {
            /** @var array{
             *     address_pool_id: int,
             *     server_id: ?int,
             *     address: string,
             *     cidr: string,
             *     gateway: string,
             *     mac_address: ?int,
             *     type: string,
             *     created_at: ?string,
             *     updated_at: ?string,
             * } $payload
             */
            $payload = [];

            foreach ($addresses as $address) {
                if (! array_key_exists($address->node_id, $pools)) {
                    $poolId = DB::table('address_pools')->insertGetId([
                        'name' => "Node {$address->fqdn}",
                    ]);

                    DB::table('address_pool_to_node')->insert([
                        'address_pool_id' => $poolId,
                        'node_id' => $address->node_id,
                    ]);

                    $pools[$address->node_id] = $poolId;
                } else {
                    $poolId = $pools[$address->node_id];
                }

                $payload[] =
                    [
                        'address_pool_id' => $poolId,
                        'server_id' => $address->server_id,
                        'address' => $address->address,
                        'cidr' => $address->cidr,
                        'gateway' => $address->gateway,
                        'mac_address' => $address->mac_address,
                        'type' => $address->type,
                        'created_at' => $address->created_at,
                        'updated_at' => $address->updated_at,
                    ];
            }

            DB::table('ip_addresses')->insert($payload);
        });

        Schema::dropIfExists('temp_ip_addresses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropForeign(['address_pool_id']);
            $table->dropForeign(['server_id']);
        });

        Schema::rename('ip_addresses', 'temp_ip_addresses');

        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address');
            $table->string('cidr');
            $table->string('gateway');
            $table->string('mac_address')->nullable();
            $table->string('type');
            $table->timestamps();
        });

        // chunk by 10 is intentional because an address pool can have MANY addresses
        DB::table('address_pools')->orderBy('id')->chunk(10, function (Collection $pools) {
            /** @var array{
             *     node_id: int,
             *     server_id: ?int,
             *     address: string,
             *     cidr: string,
             *     gateway: string,
             *     mac_address: ?int,
             *     type: string,
             *     created_at: ?string,
             *     updated_at: ?string,
             * } $payload
             */
            $payload = [];

            foreach ($pools as $pool) {
                /** @var int[] $linkedNodeIds */
                $linkedNodeIds = Arr::pluck(DB::table('address_pool_to_node')->where('address_pool_id', '=', $pool->id)->select('address_pool_to_node.node_id')->get(), 'node_id');

                $addresses = DB::table('temp_ip_addresses')->where('address_pool_id', '=', $pool->id)->get();
                foreach ($addresses as $address) {
                    foreach ($linkedNodeIds as $linkedNodeId) {
                        $payload[] = [
                            'node_id' => $linkedNodeId,
                            'server_id' => $address->server_id,
                            'address' => $address->address,
                            'cidr' => $address->cidr,
                            'gateway' => $address->gateway,
                            'mac_address' => $address->mac_address,
                            'type' => $address->type,
                            'created_at' => $address->created_at,
                            'updated_at' => $address->updated_at,

                        ];
                    }
                }
            }

            DB::table('ip_addresses')->insert($payload);
            DB::table('address_pools')->whereIn('id', Arr::pluck($pools, 'id'))->delete();
        });

        Schema::dropIfExists('temp_ip_addresses');
    }
};
