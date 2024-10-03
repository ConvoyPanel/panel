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
        Schema::create('snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('snapshot_id')->nullable()->constrained('snapshots')->cascadeOnDelete(
            );
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->string('errors')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snapshots');
    }
};
