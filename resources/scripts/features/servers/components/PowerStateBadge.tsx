import { cn } from '@/utils'

type State = App.Enums.Server.State

const labels: Record<State, string> = {
    running: 'Running',
    stopped: 'Stopped',
    starting: 'Starting',
    stopping: 'Stopping',
    shutting_down: 'Shutting down',
}

const dots: Record<State, string> = {
    running: 'bg-green-600',
    stopped: 'bg-destructive',
    starting: 'bg-yellow-500',
    stopping: 'bg-yellow-500',
    shutting_down: 'bg-yellow-500',
}

interface Props {
    /** null means the poller has nothing to say -- not that the guest is off. */
    state: State | null
    className?: string
}

/**
 * A guest's power state in a list, as of the last poll.
 *
 * Deliberately says "Unknown" rather than rendering nothing when there is no
 * answer. This state is read from a cache the scheduler fills, so "no answer"
 * is a real and reachable condition -- the node is unreachable, or nothing has
 * polled it yet. Silently omitting it would leave the row looking like a server
 * with no power at all, and defaulting it to "Stopped" would be worse: someone
 * would press Start on a machine that is already running. See
 * docs/node-status-plan.md ("Unknown is not stopped").
 */
const PowerStateBadge = ({ state, className }: Props) => (
    <span
        className={cn(
            'text-muted-foreground flex shrink-0 items-center gap-1.5 text-xs',
            className
        )}
    >
        <span
            className={cn(
                'h-1.5 w-1.5 shrink-0 rounded-full',
                state ? dots[state] : 'bg-muted-foreground/40'
            )}
        />
        {state ? labels[state] : 'Unknown'}
    </span>
)

export default PowerStateBadge
