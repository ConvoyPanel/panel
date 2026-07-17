<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anchors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('mode');
            $table->string('public_url');
            $table->text('secret');
            $table->foreignId('relay_id')->nullable()->constrained('anchors')->nullOnDelete();
            $table->string('enrollment_token_hash', 64)->nullable()->unique();
            $table->timestamp('enrollment_expires_at')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('version')->nullable();
            $table->unsignedSmallInteger('protocol_min')->nullable();
            $table->unsignedSmallInteger('protocol_max')->nullable();
            $table->json('capabilities')->nullable();
            $table->timestamps();
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coterm_id');
            $table->foreignId('anchor_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('coterms');
    }

    public function down(): void
    {
        Schema::create('coterms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_tls_enabled')->default(true);
            $table->string('fqdn');
            $table->integer('port')->default(443);
            $table->string('token_id')->unique();
            $table->text('token');
            $table->timestamps();
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anchor_id');
            $table->foreignId('coterm_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('anchors');
    }
};
