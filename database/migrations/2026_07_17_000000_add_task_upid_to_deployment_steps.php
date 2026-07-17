<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A step that drives an asynchronous Proxmox task (clone, stop, delete)
     * records that task's UPID here once it has been kicked off. It is the
     * durable "have I already started the remote work?" marker: a single job
     * both starts the task and polls it to completion, and guards the start on
     * this being null so a released/retried run never re-issues the command.
     */
    public function up(): void
    {
        Schema::table('deployment_steps', function (Blueprint $table) {
            $table->string('task_upid')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('deployment_steps', function (Blueprint $table) {
            $table->dropColumn('task_upid');
        });
    }
};
