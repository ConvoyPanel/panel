<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The durable record of one Anchor-transported migration.
 *
 * A cluster migration needs nothing like this: PVE holds the whole operation
 * in one task and the panel follows a UPID. An Anchor migration is six remote
 * calls across two nodes, and the facts that tie them together -- which
 * artifact, which hash, which VMID was taken on the destination -- outlive the
 * job that learned them. Keeping them on a row rather than passing them down a
 * `Bus::chain` is what makes the rollback able to run from anywhere in the
 * chain, and what stops a retried attempt leaving a guest behind.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_migration_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_node_id')->constrained('nodes')->restrictOnDelete();
            $table->foreignId('destination_node_id')->constrained('nodes')->restrictOnDelete();

            // The guest's identity on each side. They differ whenever the
            // source's VMID is already taken on the destination, which is the
            // case this whole transport exists to serve.
            $table->unsignedInteger('source_vmid');
            $table->unsignedInteger('destination_vmid');
            $table->string('destination_storage');

            // Filled in as the transfer proceeds; each one is the handle the
            // rollback needs to undo the step that produced it.
            $table->string('export_job_id')->nullable();
            $table->string('install_job_id')->nullable();
            $table->string('artifact')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            // An artifact has a TTL on the node and does not survive an Anchor
            // restart, so "the archive is gone" is an ordinary thing to find
            // half way through and the answer to it is to dump again. Counted
            // so that a node dropping every artifact is a migration that stops
            // rather than one that exports forever.
            $table->unsignedSmallInteger('export_attempts')->default(0);

            // Whether the guest was running when the operator asked, so a
            // rollback puts it back the way it was found rather than the way
            // the migration left it.
            $table->boolean('was_running')->default(false);

            // Set once the destination guest is verified. The source guest is
            // destroyed only after this, and nothing may destroy it without
            // it.
            $table->timestamp('verified_at')->nullable();

            // The point of no return, and the only thing a rollback refuses to
            // run after. Deliberately not `verified_at`: between the two the
            // destination is good *and* the source still exists, so a failure
            // in that window is still recoverable by going back, and the whole
            // design depends on that window having no exceptions in it.
            $table->timestamp('source_destroyed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_migration_transfers');
    }
};
