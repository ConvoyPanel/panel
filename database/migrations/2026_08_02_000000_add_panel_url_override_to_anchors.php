<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where an anchor should reach the panel, when that differs from APP_URL.
     *
     * The counterpart to `public_url`, which is how the panel reaches the
     * anchor. Null keeps the previous behaviour of using APP_URL, so existing
     * installations are unaffected.
     */
    public function up(): void
    {
        Schema::table('anchors', function (Blueprint $table) {
            $table->string('panel_url_override', 2048)->nullable()->after('public_url');
        });
    }

    public function down(): void
    {
        Schema::table('anchors', function (Blueprint $table) {
            $table->dropColumn('panel_url_override');
        });
    }
};
