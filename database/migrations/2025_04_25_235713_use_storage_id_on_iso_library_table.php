<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iso_library', function (Blueprint $table) {
            $table->foreignId('storage_id')->after('uuid')->constrained()->onDelete('cascade');
            $table->dropForeign(['node_id']);
            $table->dropColumn('node_id');
            $table->dropColumn('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('iso_library', function (Blueprint $table) {
            $table->dropForeign(['storage_id']);
            $table->dropColumn('storage_id');
            $table->foreignId('node_id')->after('uuid')->constrained()->onDelete('cascade');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }
};
