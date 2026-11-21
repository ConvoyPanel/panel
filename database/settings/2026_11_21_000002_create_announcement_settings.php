<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * `announcements.notified`: the ids of the feed announcements the admins have
 * already been emailed about, so each is mailed once.
 *
 * 5.x kept them in an `announcement_notifications` table, one row per id with a
 * recipient count nothing read. It's panel-wide state, which is what settings are
 * for, so an upgrade moves the ids here and drops the table. The table's unique
 * key was also the claim that stopped two runs mailing the same announcement; with
 * a list, the run that mails them needs a lock or `withoutOverlapping()` instead.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        // Guarded like the other settings migrations: a settings row outlives a schema
        // rollback, and `add()` throws on a property that already exists.
        if (! $this->migrator->exists('announcements.notified')) {
            $notified = Schema::hasTable('announcement_notifications')
                ? DB::table('announcement_notifications')->orderBy('id')->pluck('announcement_id')->all()
                : [];

            $this->migrator->add('announcements.notified', $notified);
        }

        Schema::dropIfExists('announcement_notifications');
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('announcements.notified');
    }
};
