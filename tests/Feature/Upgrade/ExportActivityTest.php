<?php

use Convoy\Console\Commands\Activity\ExportActivityCommand;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function seedActivity(): array
{
    [$user, , , $server] = createServerModel();

    $first = DB::table('activity_logs')->insertGetId([
        'batch' => null,
        'event' => 'server:backup.create',
        'ip' => '203.0.113.7',
        'description' => 'Made a backup, "nightly", before the upgrade',
        'actor_type' => User::class,
        'actor_id' => $user->id,
        'properties' => json_encode(['name' => 'nightly', 'note' => "two\nlines"]),
        'timestamp' => '2026-11-01 12:00:00',
    ]);
    DB::table('activity_log_subjects')->insert(['activity_log_id' => $first, 'subject_type' => Server::class, 'subject_id' => $server->id]);

    DB::table('activity_logs')->insert([
        'event' => 'auth:login',
        'ip' => '198.51.100.1',
        'properties' => '{}',
        'timestamp' => '2026-11-02 08:30:00',
    ]);

    return [$user, $server];
}

/** @return list<array<string, string>> */
function parseCsv(string $csv): array
{
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $csv);
    rewind($stream);

    $header = fgetcsv($stream, escape: '');
    $rows = [];

    while (($row = fgetcsv($stream, escape: '')) !== false) {
        $rows[] = array_combine($header, $row);
    }

    return $rows;
}

it('writes the activity log to stdout as CSV and nothing else', function () {
    [$user, $server] = seedActivity();

    expect(Artisan::call('activity:export'))->toBe(0);

    $output = Artisan::output();
    $rows = parseCsv($output);

    expect(str_starts_with($output, implode(',', ExportActivityCommand::COLUMNS)."\n"))->toBeTrue()
        ->and($rows)->toHaveCount(2)
        ->and($rows[0])->toMatchArray([
            'timestamp' => '2026-11-01T12:00:00Z',
            'event' => 'server:backup.create',
            'ip' => '203.0.113.7',
            'description' => 'Made a backup, "nightly", before the upgrade',
            'actor_type' => 'User',
            'actor_id' => (string) $user->id,
            'actor_email' => $user->email,
            'subjects' => "Server:{$server->id}",
        ])
        ->and(json_decode($rows[0]['properties'], true))->toBe(['name' => 'nightly', 'note' => "two\nlines"])
        ->and($rows[1])->toMatchArray(['event' => 'auth:login', 'actor_type' => '', 'subjects' => '', 'properties' => '[]']);
});

it('writes to a file with --output', function () {
    seedActivity();
    $path = tempnam(sys_get_temp_dir(), 'activity');

    $this->artisan('activity:export', ['--output' => $path])->assertSuccessful();

    expect(parseCsv(file_get_contents($path)))->toHaveCount(2);

    unlink($path);
});

it('writes just the header when there is no activity', function () {
    Artisan::call('activity:export');

    expect(Artisan::output())->toBe(implode(',', ExportActivityCommand::COLUMNS)."\n");
});
