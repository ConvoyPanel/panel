<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            // drop the foreign key on server_id
            $table->dropForeign(['server_id']);
            $table->dropColumn(['server_id', 'visible']);
            $table->after('id', function (Blueprint $table) {
                $table->foreignId('template_group_id')->constrained('template_groups')->onDelete('cascade');
                $table->string('name');
                $table->unsignedBigInteger('vmid');
                $table->boolean('hidden')->default(false);
                $table->unsignedBigInteger('order_column');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->unsignedBigInteger('server_id')->after('id');
            $table->boolean('visible')->after('server_id');
            $table->foreign('server_id')->references('id')->on('servers');
            $table->dropForeign(['template_group_id']);
            $table->dropColumn(['template_group_id', 'name', 'vmid', 'hidden', 'order_column']);
        });
    }
};
