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
        Schema::create('storages', function (Blueprint $table) {
            $table->id();
            $table->string('nickname')->nullable();
            $table->string('description')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('size');
            $table->boolean('is_shareable')->default(false);
            $table->boolean('has_kvm');
            $table->boolean('has_lxc');
            $table->boolean('has_lxc_templates');
            $table->boolean('has_backups');
            $table->boolean('has_isos');
            $table->boolean('has_snippets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storages');
    }
};
