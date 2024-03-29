<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coterms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_tls_enabled');
            $table->string('fqdn');
            $table->integer('port');
            $table->string('token_id');
            $table->string('token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coterms');
    }
};
