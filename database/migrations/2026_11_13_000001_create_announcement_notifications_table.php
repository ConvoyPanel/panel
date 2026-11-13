<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per announcement the admins were emailed about, so each is mailed once.
        Schema::create('announcement_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('announcement_id', 120)->unique();
            $table->unsignedInteger('recipients')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_notifications');
    }
};
