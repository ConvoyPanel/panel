<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropForeign(['server_id', 'node_id']);
        });
        Schema::rename('ip_addresses', 'temp_ip_addresses');

        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('address_pool_id')->constrained()->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address');
            $table->string('cidr');
            $table->string('gateway');
            $table->string('mac_address')->nullable();
            $table->string('type');
            $table->timestamps();
        });

        DB::statement("
        INSERT INTO address_pools (name, created_at, updated_at)
SELECT DISTINCT CONCAT('Address pool for node ', (SELECT fqdn FROM nodes WHERE id=node_id)), NOW(), NOW()
FROM ip_addresses
        ");




    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('address_table_that_uses_ip_pools');
    }
};
