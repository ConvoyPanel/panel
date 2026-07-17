<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive polish for deployment progress reporting:
     *
     * - progress_mode: whether a step draws a determinate bar or an
     *   indeterminate spinner, so the UI stops inferring it from a nullable
     *   total (and steps like `configure` can be honestly indeterminate rather
     *   than carrying a magic total).
     * - sequence: the step's explicit display order, so the UI no longer relies
     *   on insertion/PK order happening to match.
     * - deployments.started_at: when the deployment actually began running, so
     *   stuck-detection measures run time rather than time-since-requested (a
     *   queue backlog no longer trips the timeout before a deployment runs).
     */
    public function up(): void
    {
        Schema::table('deployment_steps', function (Blueprint $table) {
            $table->string('progress_mode')->default('indeterminate')->after('status');
            $table->unsignedInteger('sequence')->default(0)->after('progress_mode');
        });

        Schema::table('deployments', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('deployment_steps', function (Blueprint $table) {
            $table->dropColumn(['progress_mode', 'sequence']);
        });

        Schema::table('deployments', function (Blueprint $table) {
            $table->dropColumn('started_at');
        });
    }
};
