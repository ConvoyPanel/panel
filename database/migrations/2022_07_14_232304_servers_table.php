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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('node_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('vmid');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('vnc_username')->nullable();
            $table->string('vnc_password')->nullable();
            $table->boolean('cloud_init_enabled')->default(true);
            $table->boolean('is_os_template')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
