<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->after('disk', function (Blueprint $table) {
                $table->foreignId('primary_ipv4_address_id')
                    ->nullable()
                    ->constrained('addresses')
                    ->nullOnDelete();

                $table->foreignId('primary_ipv6_address_id')
                    ->nullable()
                    ->constrained('addresses')
                    ->nullOnDelete();
            });
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_ipv4_address_id');
            $table->dropConstrainedForeignId('primary_ipv6_address_id');
        });
    }
};
