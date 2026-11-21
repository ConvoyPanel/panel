<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A server caught mid-restore, or left there by a restore that failed, would keep a
        // status the lifecycle no longer has, and loading it would throw. The restore can't
        // finish now, so the server goes back to ready.
        DB::table('servers')->where('status', 'restoring_snapshot')->update(['status' => 'ready']);

        Schema::dropIfExists('snapshots');

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['snapshot_count_limit', 'snapshot_size_limit']);
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->integer('snapshot_count_limit')->after('disk');
            $table->integer('snapshot_size_limit')->after('snapshot_count_limit');
        });

        Schema::create('snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('snapshot_id')->nullable()->constrained('snapshots')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->string('errors')->nullable();
            $table->unsignedInteger('size')->nullable(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
};
