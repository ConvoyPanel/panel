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
        Schema::table('nodes', function (Blueprint $table) {
            $table->after('network', function (Blueprint $table) {
                $table->boolean('coterm_tls_enabled')->default(true);
                $table->string('coterm_fqdn')->nullable();
                $table->integer('coterm_port')->default(443);
                $table->text('coterm_token')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn(['coterm_tls_enabled', 'coterm_fqdn', 'coterm_port', 'coterm_token']);
        });
    }
};
