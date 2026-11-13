<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('uuid', 36)->nullable()->after('id');
        });

        // Was `UPDATE users SET uuid=(select UUID())`, and MySQL's UUID() doesn't exist
        // on Postgres. Generated in PHP instead, which works on both.
        DB::table('users')->whereNull('uuid')->lazyById()->each(
            fn (object $user) => DB::table('users')->where('id', $user->id)->update(['uuid' => (string) Str::uuid()]),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
