<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_subusers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Values from App\Enums\Server\ServerPermission. An empty list is a real state: the
            // person can see the server and nothing else.
            $table->json('permissions');

            // The owner at the time of sharing. Nulled rather than cascaded so a grant survives
            // the account that made it, which matters when a server changes hands.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // One grant per person per server: a second row would make "their permissions" a
            // question with two answers.
            $table->unique(['server_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_subusers');
    }
};
