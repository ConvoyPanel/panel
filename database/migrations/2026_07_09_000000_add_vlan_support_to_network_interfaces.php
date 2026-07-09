<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('network_interfaces', function (Blueprint $table) {
            $table->boolean('is_vlan_aware')->default(false);
            $table->unsignedSmallInteger('vlan_tag')->nullable();
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->foreignId('network_interface_id')
                ->nullable()
                ->constrained('network_interfaces')
                ->nullOnDelete();
            $table->unsignedSmallInteger('vlan_tag')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('network_interface_id');
            $table->dropColumn('vlan_tag');
        });

        Schema::table('network_interfaces', function (Blueprint $table) {
            $table->dropColumn(['is_vlan_aware', 'vlan_tag']);
        });
    }
};
