<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First drop all foreign key constraints referencing templates and template_groups
        Schema::table('templates', function (Blueprint $table) {
            $table->dropForeign(['template_group_id']);
        });

        Schema::table('deployments', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        // Now we can safely truncate both tables
        DB::table('templates')->truncate();
        DB::table('template_groups')->truncate();

        // Re-add the foreign key constraints
        Schema::table('templates', function (Blueprint $table) {
            $table->foreign('template_group_id')->references('id')->on('template_groups')->onDelete('cascade');
        });

        Schema::table('deployments', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
        });

        Schema::table('template_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('node_id');
            $table->after('name', function (Blueprint $table) {
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
            });
            $table->renameColumn('hidden', 'is_admin_only');
            $table->dropColumn('order_column');
            $table->dropTimestamps();
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->renameColumn('hidden', 'is_admin_only');
            $table->dropColumn('order_column');
            $table->dropTimestamps();
        });
    }

    public function down(): void
    {
        // First drop all foreign key constraints
        Schema::table('templates', function (Blueprint $table) {
            $table->dropForeign(['template_group_id']);
        });

        Schema::table('deployments', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        // Now we can safely truncate both tables
        DB::table('templates')->truncate();
        DB::table('template_groups')->truncate();

        // Re-add the foreign key constraints
        Schema::table('templates', function (Blueprint $table) {
            $table->foreign('template_group_id')->references('id')->on('template_groups')->onDelete('cascade');
        });

        Schema::table('deployments', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
        });

        Schema::table('template_groups', function (Blueprint $table) {
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->dropColumn('description');
            $table->dropColumn('icon');
            $table->renameColumn('is_admin_only', 'hidden');
            $table->unsignedBigInteger('order_column')->after('hidden');
            $table->timestamps();
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->renameColumn('is_admin_only', 'hidden');
            $table->unsignedBigInteger('order_column')->after('hidden');
            $table->timestamps();
        });
    }
};
