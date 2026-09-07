<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_invites', function (Blueprint $table) {
            $table->id();

            // One live invite per account, enforced here rather than in the service: re-issuing
            // has to invalidate the previous link, and a unique index is what makes that true
            // even when two admins press the button at the same moment.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // The sha256 of the token, never the token. A leaked database should not hand out
            // working sign-in links, which is the same reason Sanctum stores hashes.
            $table->string('token', 64)->unique();

            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invites');
    }
};
