<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Says which unit this one is in, because it disagrees with every sibling.
 *
 * Every other size column in the schema is mebibytes on disk and bytes through
 * the model, via `StorageSizeCast`. This one is raw bytes, and deliberately: it
 * is compared against the byte length of a file a node downloads, and its
 * sibling `virtual_size` is the floor a plan's disk has to clear. The cast
 * floors to whole MiB, which would under-report that floor by up to a mebibyte
 * and let a plan through that cannot actually hold the image.
 *
 * Opting out of the convention is fine. Opting out silently, under a name
 * identical to the columns that follow it, is not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('image_versions', function (Blueprint $table) {
            $table->renameColumn('size', 'size_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('image_versions', function (Blueprint $table) {
            $table->renameColumn('size_bytes', 'size');
        });
    }
};
