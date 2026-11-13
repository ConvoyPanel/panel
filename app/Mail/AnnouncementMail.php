<?php

namespace Convoy\Mail;

use Convoy\Services\Announcements\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A critical or high announcement from the Convoy team, sent once to every
 * admin of a panel it applies to.
 */
class AnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Announcement $announcement)
    {
    }

    public function envelope(): Envelope
    {
        $label = match ($this->announcement->severity) {
            'critical' => 'Critical',
            'high' => 'Important',
            default => 'Notice',
        };

        return new Envelope(subject: "[Convoy] {$label}: {$this->announcement->title}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.announcement', with: [
            'announcement' => $this->announcement,
            'updatesUrl' => rtrim((string) config('app.url'), '/').'/admin/settings/updates',
        ]);
    }
}
