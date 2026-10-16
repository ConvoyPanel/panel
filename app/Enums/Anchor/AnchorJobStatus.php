<?php

namespace App\Enums\Anchor;

/**
 * Where a job on an Anchor has got to.
 *
 * The same ladder the agent already publishes for a template install, plus the
 * two phases an export adds. Phases are ordered and `progress` is read against
 * whichever one is current, so the panel renders "exporting 62%" rather than
 * blending two very differently paced operations into one bar.
 */
enum AnchorJobStatus: string
{
    case Pending = 'pending';
    /** `vzdump` is running. The long phase of an export. */
    case Dumping = 'dumping';
    case Exporting = 'exporting';
    case Hashing = 'hashing';
    case Downloading = 'downloading';
    case Verifying = 'verifying';
    case Restoring = 'restoring';
    case Finalizing = 'finalizing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /**
     * A status this panel does not recognise means the agent is newer than we
     * are, not that the job is over. Reading it as "still working" keeps the
     * poller polling until the job reaches a terminal state we do know, or
     * until the step's own deadline expires; both of those are recoverable.
     * Reading it as a failure would abandon a job that is going fine.
     */
    public static function parse(mixed $raw): self
    {
        return is_string($raw) ? (self::tryFrom($raw) ?? self::Pending) : self::Pending;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled], true);
    }
}
