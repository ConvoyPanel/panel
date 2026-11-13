<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two additions the image tables need: where a build came from, and a place to
 * park an upload that has not finished arriving.
 *
 * A registry is a catalogue, not a concept, so nothing here models one. The
 * catalogue is a URL the panel reads; what it leaves behind is the slug the
 * import came from, which is the only fact needed to recognise the same
 * template on a later fetch and to answer "is there a newer build of this?"
 * without keeping a second copy of the catalogue in the database.
 *
 * `image_uploads` exists because a disk image is measured in gigabytes and a
 * single request is not a place to put one. It holds the bookkeeping for a
 * resumable upload (how much has landed, what it is supposed to weigh) and is
 * deleted the moment the file is assembled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('image_groups', function (Blueprint $table) {
            // Which catalogue entry this group mirrors. Unique so a re-import
            // finds the group it made last time instead of making another.
            $table->string('registry_slug')->nullable()->unique();
        });

        Schema::table('image_definitions', function (Blueprint $table) {
            $table->string('registry_slug')->nullable()->unique();
        });

        Schema::table('image_versions', function (Blueprint $table) {
            // manual: disks the operator uploaded. url: origins they gave us.
            // registry: imported from a catalogue, and the only one of the
            // three for which "a newer build exists" is a question with an
            // answer.
            $table->string('source')->default('manual');
        });

        Schema::create('image_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid();

            // Who may resume it. Nulled rather than cascaded so a deleted
            // account does not take a half-written file's bookkeeping with it
            // and leave the bytes orphaned on disk.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('file_name');
            $table->string('format');

            // What the client said it was sending. Both are checked at the end:
            // the size against what actually landed, the hash against what the
            // assembled file hashes to.
            $table->unsignedBigInteger('expected_size');
            $table->string('expected_sha256')->nullable();

            $table->unsignedBigInteger('received_bytes')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_uploads');

        Schema::table('image_versions', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('image_definitions', function (Blueprint $table) {
            $table->dropUnique(['registry_slug']);
            $table->dropColumn('registry_slug');
        });

        Schema::table('image_groups', function (Blueprint $table) {
            $table->dropUnique(['registry_slug']);
            $table->dropColumn('registry_slug');
        });
    }
};
