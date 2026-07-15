<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Null/empty means unrestricted. JSON keeps the rule order stable for the admin UI
            // and supports both individual IPv4/IPv6 addresses and CIDR ranges.
            $table->jsonb('allowed_networks')->nullable()->after('abilities');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn('allowed_networks');
        });
    }
};
