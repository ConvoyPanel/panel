<?php

namespace Convoy\Services\Announcements;

use Carbon\CarbonImmutable;
use Convoy\Mail\AnnouncementMail;
use Convoy\Models\AnnouncementDismissal;
use Convoy\Models\AnnouncementNotification;
use Convoy\Models\User;
use Convoy\Services\Mail\MailConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * What the verified feed means for this panel: which announcements apply, and
 * why; who has dismissed what; and the one mail per urgent announcement.
 */
class AnnouncementService
{
    /**
     * Not a feed id (those are slugs of lowercase letters, digits and dashes),
     * so it can't collide with one: the notice that telemetry is on, which each
     * admin dismisses once like an announcement.
     */
    public const TELEMETRY_NOTICE = 'local:telemetry-notice';

    public function __construct(private AnnouncementFeed $feed)
    {
    }

    /** @return list<Announcement> newest first */
    public function all(): array
    {
        return $this->feed->announcements();
    }

    public function find(string $id): ?Announcement
    {
        foreach ($this->all() as $announcement) {
            if ($announcement->id === $id) {
                return $announcement;
            }
        }

        return null;
    }

    public function panelVersion(): string
    {
        return (string) config('app.version');
    }

    /** PHP's version without a distribution's suffix ("8.4.12", not "8.4.12-1+deb13"). */
    public function phpVersion(): string
    {
        return sprintf('%d.%d.%d', PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION);
    }

    public function applies(Announcement $announcement): bool
    {
        return ! $announcement->expired()
            && VersionRanges::matches($announcement->panel, $this->panelVersion())
            && VersionRanges::matches($announcement->php, $this->phpVersion());
    }

    /**
     * Why an announcement does or doesn't apply to this panel, one sentence per
     * check, for Settings → Updates. `matches` says whether that check holds for
     * this panel; the announcement applies when every one does.
     *
     * @return list<array{text: string, matches: bool}>
     */
    public function reasons(Announcement $announcement): array
    {
        $panel = $this->panelVersion();
        $php = $this->phpVersion();
        $reasons = [];

        if ($announcement->expired()) {
            $reasons[] = ['text' => "It expired on {$announcement->expiresAt->toFormattedDateString()}.", 'matches' => false];
        }

        if (is_null($announcement->panel)) {
            $reasons[] = ['text' => 'It is for every Convoy version.', 'matches' => true];
        } elseif (! VersionRanges::comparable($panel)) {
            $reasons[] = [
                'text' => 'It is for Convoy '.VersionRanges::describe($announcement->panel).". This panel reports \"{$panel}\" (built from source), which never matches a Convoy version range.",
                'matches' => false,
            ];
        } elseif (VersionRanges::matches($announcement->panel, $panel)) {
            $reasons[] = ['text' => "This panel runs Convoy {$panel}, which is affected (".VersionRanges::describe($announcement->panel).').', 'matches' => true];
        } else {
            $reasons[] = ['text' => "This panel runs Convoy {$panel}. It is for Convoy ".VersionRanges::describe($announcement->panel).'.', 'matches' => false];
        }

        if (! is_null($announcement->php)) {
            $reasons[] = VersionRanges::matches($announcement->php, $php)
                ? ['text' => "This panel runs PHP {$php}, which is affected (".VersionRanges::describe($announcement->php).').', 'matches' => true]
                : ['text' => "This panel runs PHP {$php}. It is for PHP ".VersionRanges::describe($announcement->php).'.', 'matches' => false];
        }

        return $reasons;
    }

    /**
     * The announcements to put in front of an admin: critical ones as a banner on
     * every page, the rest on the overview until dismissed.
     *
     * @return array{banner: list<Announcement>, overview: list<Announcement>}
     */
    public function forAdmin(User $user): array
    {
        $dismissed = $this->dismissedBy($user);
        $banner = [];
        $overview = [];

        foreach ($this->all() as $announcement) {
            if (! $this->applies($announcement)) {
                continue;
            }

            if ($announcement->critical()) {
                $banner[] = $announcement;
            } elseif (! in_array($announcement->id, $dismissed, true)) {
                $overview[] = $announcement;
            }
        }

        return ['banner' => $banner, 'overview' => $overview];
    }

    /** @return list<string> */
    public function dismissedBy(User $user): array
    {
        return AnnouncementDismissal::query()
            ->where('user_id', $user->id)
            ->pluck('announcement_id')
            ->all();
    }

    /** Critical announcements can't be dismissed; they go away when they stop applying. */
    public function dismiss(User $user, string $id): void
    {
        DB::table('announcement_dismissals')->insertOrIgnore([
            'user_id' => $user->id,
            'announcement_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Emails every admin about each critical or high announcement that applies
     * to this panel and hasn't been mailed before. Does nothing while mail isn't
     * configured, so an announcement that still applies once it is gets mailed
     * then.
     *
     * @return list<string> The ids mailed about
     */
    public function notify(): array
    {
        if (! MailConfiguration::configured()) {
            return [];
        }

        $admins = User::query()->where('root_admin', true)->orderBy('id')->get();

        if ($admins->isEmpty()) {
            return [];
        }

        $mailed = [];

        foreach ($this->all() as $announcement) {
            if (! $announcement->urgent() || ! $this->applies($announcement)) {
                continue;
            }

            // Claimed before anything is queued, and atomically, so two runs at once can't both
            // send it.
            $claimed = DB::table('announcement_notifications')->insertOrIgnore([
                'announcement_id' => $announcement->id,
                'recipients' => $admins->count(),
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ]);

            if ($claimed === 0) {
                continue;
            }

            foreach ($admins as $admin) {
                Mail::to($admin->email, $admin->name)->queue(new AnnouncementMail($announcement));
            }

            $mailed[] = $announcement->id;
        }

        return $mailed;
    }

    public function notified(string $id): bool
    {
        return AnnouncementNotification::query()->where('announcement_id', $id)->exists();
    }
}
