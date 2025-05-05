<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('mtu')->default(1500);
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('network');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_interfaces');

        Schema::table('nodes', function (Blueprint $table) {
            $table->string('network')->nullable()->after('memory_overallocate');
        });
    }
};
