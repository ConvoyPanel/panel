<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes an ISO a panel record instead of a file on one node's storage.
 *
 * An `iso_library` row used to *be* a download onto a particular storage: it
 * carried `storage_id`, and `is_successful`/`completed_at` tracked whether that
 * one transfer worked. So the same ISO on four nodes was four rows, an operator
 * had to think about placement before a user could ever mount it, and a node
 * added later simply did not have it.
 *
 * Now a row is the ISO itself -- a name, a source, and a hash -- and residency
 * is a question asked of a node at mount time, exactly as it is for disk
 * images. The source is either a URL the operator hosts or a file uploaded to
 * the panel, and the panel serves the second over a signed link.
 *
 * The rows are dropped rather than migrated. What they hold is a filename on a
 * storage and no hash and no source; there is no way to turn that into a record
 * that another node could fetch, which is the entire point of the new shape.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('iso_library')->delete();

        Schema::table('iso_library', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_id');
            $table->dropColumn(['is_successful', 'completed_at']);

            // Exactly one is set. Both answer the same question -- what URL
            // returns these bytes -- so a node never learns which it was given.
            $table->string('url')->nullable()->after('name');
            $table->string('path')->nullable()->after('url');

            // Content addressing is what lets a node prove it fetched the right
            // file from a source the panel does not have to trust, and what
            // makes "do I already have this?" answerable by name alone.
            $table->string('sha256', 64)->nullable()->after('path');
        });
    }

    public function down(): void
    {
        DB::table('iso_library')->delete();

        Schema::table('iso_library', function (Blueprint $table) {
            $table->dropColumn(['url', 'path', 'sha256']);
            $table->foreignId('storage_id')->constrained('storages')->cascadeOnDelete();
            $table->boolean('is_successful')->default(false);
            $table->timestamp('completed_at')->nullable();
        });
    }
};
