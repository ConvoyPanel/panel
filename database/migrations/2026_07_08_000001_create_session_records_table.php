<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * First-party session metadata. Laravel's session listing is a feature of the *database* session
 * driver; this install runs sessions on Redis, so we track the metadata ourselves to power an
 * "active sessions" account page. The raw `session_id` is kept server-side only (needed to destroy
 * the Redis session on revoke) — the API exposes the numeric row id instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_records', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_active_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_records');
    }
};
