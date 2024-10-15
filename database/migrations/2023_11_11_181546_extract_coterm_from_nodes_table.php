<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropUnique(['coterm_token_id']);
            $table->dropColumn(
                ['coterm_enabled', 'coterm_tls_enabled', 'coterm_fqdn', 'coterm_port', 'coterm_token_id', 'coterm_token'],
            );

            $table->foreignId('coterm_id')->after('network')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->after('network', function (Blueprint $table) {
                $table->boolean('coterm_enabled')->default(false);
                $table->boolean('coterm_tls_enabled')->default(true);
                $table->string('coterm_fqdn')->nullable();
                $table->integer('coterm_port')->default(443);
                $table->string('coterm_token_id')->nullable()->unique();
                $table->text('coterm_token')->nullable();
            });

            $table->dropForeign(['coterm_id']);
            $table->dropColumn('coterm_id');
        });
    }
};
