<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cluster')->default('proxmox');
            $table->string('hostname');
            $table->string('username');
            $table->string('password');
            $table->integer('port');
            $table->string('auth_type');
            //$table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->integer('latency')->nullable();
            $table->timestamp('last_pinged')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
