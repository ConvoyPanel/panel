<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Panel-wide (application) API tokens must outlive the admin who created them, so they can't be
     * owned by a user. They instead belong to a single "system actor" — a stable, user-independent
     * identity that represents the panel — while `created_by` records the minting admin for audit
     * (nulled, not cascaded, if that admin is deleted).
     */
    public function up(): void
    {
        Schema::create('system_actors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('System');
            $table->timestamps();
        });

        // The singleton the application tokens hang off of.
        DB::table('system_actors')->insert([
            'name' => 'System',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('type')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::dropIfExists('system_actors');
    }
};
