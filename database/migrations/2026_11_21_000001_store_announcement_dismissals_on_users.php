<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The feed announcements each user has dismissed, as a JSON list of announcement
 * ids on their own row. Null means none.
 *
 * 5.x kept these in an `announcement_dismissals` table, one row per user and id.
 * Nothing ever asked more of it than "which has this user dismissed", which a
 * column read with the user answers without a query, so an upgrade moves the rows
 * here and drops the table. The ids are the feed's own (docs/announcements-feed.md
 * in 5.x), so the list only grows by what the feed publishes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'dismissed_announcements')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('dismissed_announcements')->nullable();
            });
        }

        if (! Schema::hasTable('announcement_dismissals')) {
            return;
        }

        $dismissals = DB::table('announcement_dismissals')
            ->orderBy('id')
            ->get(['user_id', 'announcement_id'])
            ->groupBy('user_id');

        foreach ($dismissals as $userId => $rows) {
            DB::table('users')->where('id', $userId)->update([
                'dismissed_announcements' => json_encode($rows->pluck('announcement_id')->unique()->values()->all()),
            ]);
        }

        Schema::drop('announcement_dismissals');
    }

    public function down(): void
    {
        // 5.x's table isn't recreated: a fresh install never had it.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dismissed_announcements');
        });
    }
};
