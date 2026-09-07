<?php

use App\Enums\Audit\AuditEvent;
use App\Enums\Mail\MailEncryption;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Mail\TestMailMessage;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create(['root_admin' => true]);
});

/**
 * Clear the stored settings. The install migration imports whatever MAIL_* the environment
 * already had, so a test about the *unset* state has to blank them rather than assume them.
 */
function blankMailSettings(): void
{
    $settings = app(MailSettings::class);
    $settings->host = '';
    $settings->username = '';
    $settings->password = '';
    $settings->from_address = '';
    $settings->from_name = '';
    $settings->save();
}

it('adopts the environment SMTP settings when it is installed', function () {
    // The whole point of importing rather than cascading: after install the form shows what is
    // actually being used, so an empty host can be trusted to mean "nothing is set".
    $this->actingAs($this->user)
        ->getJson('/api/admin/settings/mail')
        ->assertOk()
        ->assertJsonPath('data.host', config('mail.mailers.smtp.host'))
        ->assertJsonPath('data.port', (int) config('mail.mailers.smtp.port'))
        ->assertJsonPath('data.fromAddress', config('mail.from.address'))
        ->assertJsonPath('data.configured', true);
});

it('reports that nothing can deliver once the form is cleared', function () {
    // `array` accepts every message and delivers none, which is exactly the state the screen
    // must not describe as working.
    blankMailSettings();
    config()->set('mail.default', 'array');

    $this->actingAs($this->user)
        ->getJson('/api/admin/settings/mail')
        ->assertOk()
        ->assertJsonPath('data.host', '')
        ->assertJsonPath('data.passwordSet', false)
        ->assertJsonPath('data.configured', false);
});

it('does not fall back to MAIL_* once the form is cleared', function () {
    // The install migration imported whatever MAIL_* held, so the form is the only place SMTP
    // comes from. A blank host with MAIL_HOST still set in the environment means the operator
    // cleared it deliberately — reading it back would restore the second source of truth this
    // whole change removed.
    blankMailSettings();
    config()->set('mail.default', 'smtp');
    config()->set('mail.mailers.smtp.host', 'smtp.example.com');

    $this->actingAs($this->user)
        ->getJson('/api/admin/settings/mail')
        ->assertOk()
        ->assertJsonPath('data.host', '')
        ->assertJsonPath('data.configured', false);
});

it('counts a transport this screen does not own as configured', function () {
    // ses/postmark/resend carry credentials there are no fields for here. Reporting "not
    // configured" would send an operator to fix an outage they do not have.
    blankMailSettings();
    config()->set('mail.default', 'ses');

    $this->actingAs($this->user)
        ->getJson('/api/admin/settings/mail')
        ->assertOk()
        ->assertJsonPath('data.configured', true);
});

it('does not count a non-delivering transport as configured', function () {
    // `log` writes to a file and reports success. Treating it as working would be the
    // single most misleading thing this screen could say.
    blankMailSettings();
    config()->set('mail.default', 'log');

    $this->actingAs($this->user)
        ->getJson('/api/admin/settings/mail')
        ->assertOk()
        ->assertJsonPath('data.configured', false);
});

it('stores what it was given', function () {
    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/mail', [
            'host' => 'smtp.example.com',
            'port' => 465,
            'username' => 'postmaster',
            'password' => 'hunter2',
            'encryption' => 'ssl',
            'from_address' => 'panel@example.com',
            'from_name' => 'Convoy',
        ])
        ->assertOk()
        ->assertJsonPath('data.host', 'smtp.example.com')
        ->assertJsonPath('data.encryption', 'ssl')
        ->assertJsonPath('data.passwordSet', true)
        ->assertJsonPath('data.configured', true);

    $settings = app(MailSettings::class)->refresh();

    expect($settings->encryption)->toBe(MailEncryption::SSL)
        ->and($settings->password)->toBe('hunter2')
        ->and($settings->isConfigured())->toBeTrue();
});

it('never returns the password it stores', function () {
    $this->actingAs($this->user)->putJson('/api/admin/settings/mail', [
        'host' => 'smtp.example.com',
        'port' => 587,
        'password' => 'hunter2',
        'encryption' => 'tls',
        'from_address' => 'panel@example.com',
        'from_name' => 'Convoy',
    ]);

    // Write-only across the API: an admin session must not be usable to read back a
    // relay credential somebody else typed.
    $body = $this->actingAs($this->user)->getJson('/api/admin/settings/mail')->json('data');

    expect($body)->not->toHaveKey('password')
        ->and(json_encode($body))->not->toContain('hunter2');
});

it('keeps the stored password when the key is absent', function () {
    $settings = app(MailSettings::class);
    $settings->host = 'smtp.example.com';
    $settings->password = 'hunter2';
    $settings->from_address = 'panel@example.com';
    $settings->from_name = 'Convoy';
    $settings->save();

    // The screen cannot echo a password it was never sent, so "change the port" must not
    // silently blank the credential.
    $this->actingAs($this->user)->putJson('/api/admin/settings/mail', [
        'host' => 'smtp.example.com',
        'port' => 2525,
        'encryption' => 'tls',
        'from_address' => 'panel@example.com',
        'from_name' => 'Convoy',
    ])->assertOk()->assertJsonPath('data.passwordSet', true);

    expect(app(MailSettings::class)->refresh()->password)->toBe('hunter2');
});

it('clears the credential along with the host', function () {
    $settings = app(MailSettings::class);
    $settings->host = 'smtp.example.com';
    $settings->password = 'hunter2';
    $settings->save();

    config()->set('mail.default', 'array');

    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/mail', ['host' => ''])
        ->assertOk()
        ->assertJsonPath('data.passwordSet', false)
        ->assertJsonPath('data.configured', false);

    // Keeping a relay password for a relay the panel no longer talks to would be a
    // credential retained for no reason.
    expect(app(MailSettings::class)->refresh()->password)->toBe('');
});

it('stores the encryption mode as its backing value', function () {
    // laravel-settings resolves an EnumCast for a typed enum property on its own, so the
    // column keeps holding the same string a plain `string` property would have.
    $this->actingAs($this->user)->putJson('/api/admin/settings/mail', [
        'host' => 'smtp.example.com',
        'port' => 25,
        'encryption' => 'none',
        'from_address' => 'panel@example.com',
        'from_name' => 'Convoy',
    ])->assertOk();

    expect(app(MailSettings::class)->refresh()->encryption)->toBe(MailEncryption::NONE);
});

it('rejects an encryption mode that is not one of the three', function () {
    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/mail', [
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'starttls',
            'from_address' => 'panel@example.com',
            'from_name' => 'Convoy',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['encryption']);
});

it('requires the rest of the form once a host is given', function () {
    $this->actingAs($this->user)
        ->putJson('/api/admin/settings/mail', ['host' => 'smtp.example.com'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['port', 'encryption', 'from_address', 'from_name']);
});

it('records the change without the password', function () {
    $this->actingAs($this->user)->putJson('/api/admin/settings/mail', [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'postmaster',
        'password' => 'hunter2',
        'encryption' => 'tls',
        'from_address' => 'panel@example.com',
        'from_name' => 'Convoy',
    ]);

    $entry = AuditLog::query()
        ->where('event', '=', AuditEvent::ADMIN_SETTINGS_MAIL_UPDATED)
        ->sole();

    expect($entry->properties['host'])->toBe('smtp.example.com')
        ->and($entry->properties)->not->toHaveKey('password')
        ->and(json_encode($entry->properties))->not->toContain('hunter2')
        ->and($entry->subject_id)->toBeNull();
});

it('sends a test message to the acting admin by default', function () {
    Mail::fake();

    $this->actingAs($this->user)
        ->postJson('/api/admin/settings/mail/test', [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'postmaster',
            'password' => 'hunter2',
            'encryption' => 'tls',
            'from_address' => 'panel@example.com',
            'from_name' => 'Convoy',
        ])
        ->assertOk()
        ->assertJsonPath('data.recipient', $this->user->email);

    Mail::assertSent(TestMailMessage::class);
});

it('tests the submitted settings without saving them', function () {
    Mail::fake();
    blankMailSettings();

    $this->actingAs($this->user)->postJson('/api/admin/settings/mail/test', [
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls',
        'from_address' => 'panel@example.com',
        'from_name' => 'Convoy',
        'recipient' => 'someone@example.com',
    ])->assertOk();

    // The whole point of the button is proving credentials before committing them.
    expect(app(MailSettings::class)->refresh()->isConfigured())->toBeFalse();
});

it('is closed to a non-admin', function () {
    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->getJson('/api/admin/settings/mail')
        ->assertForbidden();
});
