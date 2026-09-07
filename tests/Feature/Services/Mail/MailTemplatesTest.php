<?php

use App\Enums\Mail\MailEncryption;
use App\Models\User;
use App\Notifications\PasswordChanged;
use App\Notifications\UserInvited;
use App\Services\Mail\TestMailMessage;

/**
 * These render the mail views for real.
 *
 * Every other mail test in the suite runs under `Mail::fake()`, which asserts
 * that something was queued and never touches the template — so a view with a
 * typo'd variable, or one deleted outright, passes all of them. Since the views
 * under resources/views/mail are build output from emails/, the thing most
 * likely to break them is a rebuild, and nothing else in the suite would notice.
 *
 * They assert on rendered content rather than markup: the design is reviewed by
 * looking at it, but "the link the recipient needs is present" is a fact a test
 * can hold onto across a redesign.
 */
it('renders the connection test with the settings it was given', function () {
    $html = (new TestMailMessage(
        fromAddress: 'convoy@example.com',
        fromName: 'Convoy',
        host: 'smtp.example.com',
        port: 465,
        encryption: MailEncryption::SSL,
    ))->render();

    expect($html)
        ->toContain('smtp.example.com')
        ->toContain('465')
        // The operator's word for the mode, not the stored backing value.
        ->toContain('Implicit TLS')
        ->not->toContain('ssl')
        ->toContain('convoy@example.com');
});

it('renders the invitation with a working link', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    $link = 'https://convoy.test/auth/invite/deadbeef';

    $html = (new UserInvited($link, 3))->toMail($user)->render();

    expect($html)
        ->toContain('Hello Ada,')
        ->toContain($link)
        ->toContain('3 days');
});

it('pluralises the invitation expiry', function () {
    $html = (new UserInvited('https://convoy.test/auth/invite/deadbeef', 1))
        ->toMail(User::factory()->create())
        ->render();

    expect($html)->toContain('1 day')->not->toContain('1 days');
});

it('renders the password notice with the address it was given', function () {
    $html = (new PasswordChanged('203.0.113.42'))
        ->toMail(User::factory()->create())
        ->render();

    expect($html)
        ->toContain('Requested from')
        ->toContain('203.0.113.42');
});

it('omits the address row when none was recorded', function () {
    // The @if in the template is the only Blade control flow that survives the
    // Maizzle build, so it is the one most worth pinning down.
    $html = (new PasswordChanged)
        ->toMail(User::factory()->create())
        ->render();

    // Phrased short enough to sit on one source line: the build preserves the
    // template's own line breaks inside a paragraph, so a longer assertion
    // would be matching against wrapping rather than against copy.
    expect($html)
        ->not->toContain('Requested from')
        ->toContain('The password on your account was just changed');
});

it('leaves no unrendered Blade in any mail view', function (string $view) {
    $source = file_get_contents(resource_path("views/mail/{$view}.blade.php"));

    // A build that mangled `{{ }}` into entities would still render, silently
    // printing the escaped expression at the recipient instead of the value.
    expect($source)
        ->not->toContain('&#123;&#123;')
        ->not->toContain('&lt;')
        // OKLCH and var() are the two things the token pipeline exists to
        // remove; either one reaching a view means the build regressed.
        ->not->toContain('oklch(')
        ->not->toContain('var(--');
})->with(['test', 'user-invited', 'password-changed']);
