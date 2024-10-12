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
        Schema::create('iso_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained()->onDelete('cascade');
            $table->boolean('is_successful')->default(false);
            $table->string('name');
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->boolean('hidden')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iso_library');
    }
};
