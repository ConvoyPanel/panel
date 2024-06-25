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
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('should_create_vm');
            $table->boolean('start_on_completion');
            $table->boolean('delete_successful');
            $table->timestamp('deleted_vm_at')->nullable();
            $table->boolean('build_successful');
            $table->integer('build_progress');
            $table->timestamp('built_vm_at')->nullable();
            $table->boolean('sync_successful');
            $table->timestamp('synced_vm_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
