<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_presets', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            // Unique so the create page's picker never shows two entries an
            // admin cannot tell apart.
            $table->string('name')->unique();
            $table->string('description')->nullable();

            /*
             * The saved half of the create form, as JSON rather than a column
             * per field. A preset is deliberately *partial* — every key is
             * optional, and one that is absent leaves the form's own default
             * alone — so a column set would be a wall of nullables that has to
             * be migrated again every time the create form grows a field.
             *
             * Values are stored in the units the form itself uses (MiB for
             * memory/disk, GiB per extra disk, MB/s for the speed cap), so
             * applying a preset is a plain field-set; the byte conversions stay
             * where they already live, at submit time.
             */
            $table->json('settings');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_presets');
    }
};
