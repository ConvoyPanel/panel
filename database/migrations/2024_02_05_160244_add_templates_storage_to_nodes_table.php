<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('template_storage')->after('disk_overallocate');
        });

        DB::statement("UPDATE nodes SET template_storage = vm_storage");
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('template_storage');
        });
    }
};
