<?php

use Convoy\Mail\AnnouncementMail;
use Convoy\Models\User;
use Convoy\Services\Announcements\AnnouncementFeed;
use Convoy\Services\Announcements\AnnouncementService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\Support\FakeAnnouncementFeed;

beforeEach(function () {
    Cache::flush();
    Mail::fake();
    config([
        'app.version' => '5.0.0',
        'convoy.releases.url' => '',
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'smtp.example.test',
    ]);

    FakeAnnouncementFeed::serve([
        FakeAnnouncementFeed::entry(['id' => 'critical', 'severity' => 'critical']),
        FakeAnnouncementFeed::entry(['id' => 'high', 'severity' => 'high']),
        FakeAnnouncementFeed::entry(['id' => 'medium', 'severity' => 'medium']),
        FakeAnnouncementFeed::entry(['id' => 'news', 'type' => 'info', 'severity' => null]),
        FakeAnnouncementFeed::entry(['id' => 'not-for-us', 'severity' => 'critical', 'affects' => ['panel' => [['before' => '4.0.0']], 'php' => null]]),
    ]);
    app(AnnouncementFeed::class)->refresh();

    $this->admins = User::factory()->count(2)->create(['root_admin' => true]);
    User::factory()->create(['root_admin' => false]);
});

it('emails every admin once about each critical or high announcement that applies', function () {
    $this->artisan('announcements:check')->assertSuccessful();
    $this->artisan('announcements:check')->assertSuccessful();

    Mail::assertQueued(AnnouncementMail::class, 4);

    foreach ($this->admins as $admin) {
        foreach (['critical', 'high'] as $id) {
            Mail::assertQueued(AnnouncementMail::class, fn (AnnouncementMail $mail) => $mail->hasTo($admin->email)
                && $mail->announcement->id === $id);
        }
    }

    $this->assertDatabaseHas('announcement_notifications', ['announcement_id' => 'critical', 'recipients' => 2]);
    $this->assertDatabaseCount('announcement_notifications', 2);
});

it('waits until mail is configured, then emails what still applies', function (string $mailer, ?string $host) {
    config(['mail.default' => $mailer, 'mail.mailers.smtp.host' => $host]);

    expect(app(AnnouncementService::class)->notify())->toBe([]);
    Mail::assertNothingQueued();
    $this->assertDatabaseCount('announcement_notifications', 0);

    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test']);

    expect(app(AnnouncementService::class)->notify())->toBe(['critical', 'high']);
})->with([
    'log' => ['log', null],
    'array' => ['array', null],
    'smtp without a host' => ['smtp', ''],
    'the example mailhog' => ['smtp', 'mailhog'],
]);

it('renders the announcement in the mail with links and code but no HTML', function () {
    $mail = new AnnouncementMail(app(AnnouncementService::class)->find('critical'));

    $mail->assertSeeInHtml('PHP2Shell: your panel', false)
        ->assertSeeInHtml('<code>convoy upgrade</code>', false)
        ->assertSeeInHtml('href="https://github.com/ConvoyPanel/panel/discussions/180"', false)
        ->assertSeeInHtml('/admin/settings/updates', false);

    expect($mail->envelope()->subject)->toBe('[Convoy] Critical: PHP2Shell: your panel\'s PHP needs patching');
});

it('escapes HTML in the body', function () {
    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry(['id' => 'html', 'body' => 'Hi <img src=x onerror=alert(1)> <b>there</b>'])], generatedAt: '2026-11-02T00:00:00Z');
    app(AnnouncementFeed::class)->refresh();

    $html = (new AnnouncementMail(app(AnnouncementService::class)->find('html')))->render();

    expect($html)->not->toContain('<img src=x')->not->toContain('<b>there</b>')->toContain('&lt;b&gt;there');
});
