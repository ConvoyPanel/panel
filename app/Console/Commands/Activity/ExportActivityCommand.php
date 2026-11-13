<?php

namespace Convoy\Console\Commands\Activity;

use Convoy\Models\ActivityLog;
use Convoy\Models\ActivityLogSubject;
use Convoy\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Writes the activity log as CSV, because Convoy 6 starts a new audit log and
 * doesn't carry it over. `convoy export-activity` runs this in the web
 * container, so stdout carries the CSV alone; everything else goes to stderr.
 */
class ExportActivityCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'activity:export {--output= : Write to this file instead of stdout}';

    /**
     * @var string
     */
    protected $description = 'Export the activity log as CSV.';

    public const COLUMNS = [
        'id', 'timestamp', 'event', 'batch', 'ip', 'description',
        'actor_type', 'actor_id', 'actor_email', 'subjects', 'properties',
    ];

    public function handle(): int
    {
        $path = $this->option('output');
        $file = is_string($path) && $path !== '' ? @fopen($path, 'w') : null;

        if ($file === false) {
            $this->getOutput()->getErrorStyle()->error("Could not open {$path} for writing.");

            return Command::FAILURE;
        }

        $write = $file
            ? fn (array $row) => fputcsv($file, $row, escape: '')
            : fn (array $row) => $this->output->write(self::csvLine($row), false, OutputInterface::OUTPUT_RAW);

        $write(self::COLUMNS);

        $count = 0;

        ActivityLog::query()
            ->with(['actor', 'subjects'])
            ->lazyById(500)
            ->each(function (ActivityLog $log) use ($write, &$count) {
                $write($this->row($log));
                $count++;
            });

        if ($file) {
            fclose($file);

            $this->getOutput()->getErrorStyle()->writeln("Wrote {$count} activity log ".($count === 1 ? 'entry' : 'entries')." to {$path}.");
        }

        return Command::SUCCESS;
    }

    /** @return list<string|int|null> */
    private function row(ActivityLog $log): array
    {
        return [
            $log->id,
            $log->timestamp?->utc()->toIso8601ZuluString(),
            $log->event,
            $log->batch,
            $log->ip,
            $log->description,
            $log->actor_type ? class_basename($log->actor_type) : null,
            $log->actor_id,
            $log->actor instanceof User ? $log->actor->email : null,
            $log->subjects
                ->map(fn (ActivityLogSubject $subject) => class_basename($subject->subject_type).':'.$subject->subject_id)
                ->implode(';'),
            json_encode($log->properties ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    /** @param  list<string|int|null>  $row */
    private static function csvLine(array $row): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $row, escape: '');
        rewind($stream);
        $line = stream_get_contents($stream);
        fclose($stream);

        return $line;
    }
}
