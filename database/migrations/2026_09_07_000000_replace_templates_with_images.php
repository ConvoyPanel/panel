<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the template tables with the image records direct import needs.
 *
 * A `Template` row was a pointer: a VMID that had to already exist as a Proxmox
 * template on whichever node a server landed on. Nothing about the guest's
 * hardware was stored, because a clone inherited it from that VMID's own config.
 * `qm create --import-from` has nothing to inherit from, so the profile has to
 * live here instead -- and once it does, the bytes and the settings turn out to
 * have different lifecycles and belong in different tables:
 *
 *  - an `image_definition` declares a type of image (its `ostype`, the hardware
 *    keys needed to boot it, its resource floor). It holds no bytes and is
 *    edited in place.
 *  - an `image_version` is one concrete build of that definition: content-addressed
 *    disks that are replaced, never edited. A rebuild is a new row, so a server
 *    can record which build it actually came from.
 *
 * There is deliberately no data migration. A template row carries a VMID and a
 * name and nothing else -- neither the disks nor the profile a definition needs
 * -- so there is nothing here that could be carried across.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id');
        });

        // Left behind by the reverted registry-import work: it recorded which
        // template was cached on which node, which is the exact coupling direct
        // import removes. No migration on this branch creates it, so this is a
        // no-op on a clean database and a tidy-up on a drifted one.
        Schema::dropIfExists('template_installs');

        Schema::dropIfExists('templates');
        Schema::dropIfExists('template_groups');

        Schema::create('image_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_admin_only')->default(false);
        });

        Schema::create('image_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('image_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_admin_only')->default(false);

            // Proxmox's own ostype. Every cloud-init decision branches on it, so
            // it is a column rather than a key inside `hardware`.
            $table->string('ostype');

            // The rest of the qm config, stored verbatim as cofoundry captured it
            // (by denylist, so keys PVE adds later survive a round trip). Columns
            // here would drop anything the panel did not know about at write time.
            $table->json('hardware')->default('{}');

            // The floor a plan must clear. Cores and memory only -- an imported
            // disk's size floor comes from the version, since it can change
            // between builds.
            $table->unsignedInteger('minimum_cores')->nullable();
            $table->unsignedBigInteger('minimum_memory')->nullable();

            $table->timestamps();

            $table->unique(['image_group_id', 'name']);
        });

        Schema::create('image_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('image_definition_id')->constrained()->cascadeOnDelete();

            // major.minor.patch, ordered by the integer triple rather than the
            // string, so 1.10.0 sorts above 1.9.0.
            $table->string('version');
            $table->unsignedInteger('version_major')->default(0);
            $table->unsignedInteger('version_minor')->default(0);
            $table->unsignedInteger('version_patch')->default(0);

            // [{slot, role, url|path, sha256, size, virtual_size, format}]
            $table->json('disks');

            // Sum of the disks' on-disk sizes, for display and transfer estimates.
            $table->unsignedBigInteger('size')->default(0);

            // A version stays readable after it is retired, because servers built
            // from it still point here.
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['image_definition_id', 'version']);
        });

        Schema::table('deployments', function (Blueprint $table) {
            // Both: the definition is what an operator chose, the version is what
            // they actually got. Nulled rather than cascaded on delete so a
            // deployment's history survives an image being removed.
            $table->foreignId('image_definition_id')->nullable()->after('server_id')->constrained()->nullOnDelete();
            $table->foreignId('image_version_id')->nullable()->after('image_definition_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('image_version_id');
            $table->dropConstrainedForeignId('image_definition_id');
        });

        Schema::dropIfExists('image_versions');
        Schema::dropIfExists('image_definitions');
        Schema::dropIfExists('image_groups');

        Schema::create('template_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_admin_only')->default(false);
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('template_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('vmid');
            $table->boolean('is_admin_only')->default(false);
        });

        Schema::table('deployments', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('server_id')->constrained()->cascadeOnDelete();
        });
    }
};
