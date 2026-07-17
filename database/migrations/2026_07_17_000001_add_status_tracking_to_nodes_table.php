<?php

use App\Enums\Node\NodeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reachability tracking for nodes (see docs/node-status-plan.md).
 *
 * Columns rather than a cache: alerting needs a state machine, and a volatile
 * store would re-alert every node the first time Redis was lost. Columns also
 * make status sortable and filterable in the Nodes table for free.
 *
 * Every node starts `unknown` -- nothing has polled yet, and claiming `online`
 * before we have asked would be a lie the UI would faithfully repeat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('status')->default(NodeStatus::UNKNOWN->value)->after('verify_tls');
            // The classified ConnectionErrorCode: why it is unreachable, not just that it is.
            $table->string('status_code')->nullable()->after('status');
            // The raw error, kept for the details disclosure the UI already has.
            $table->text('status_message')->nullable()->after('status_code');
            // Last *successful* contact -- what staleness is measured from.
            $table->timestamp('last_seen_at')->nullable()->after('status_message');
            // Last attempt, successful or not.
            $table->timestamp('status_checked_at')->nullable()->after('last_seen_at');
            // Debounce counter so a single flap never alerts (slice 3).
            $table->unsignedInteger('consecutive_failures')->default(0)->after('status_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'status_code',
                'status_message',
                'last_seen_at',
                'status_checked_at',
                'consecutive_failures',
            ]);
        });
    }
};
