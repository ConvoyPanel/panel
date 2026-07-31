import { cn } from '@/utils'

type State = App.Enums.Server.PowerState

const labels: Record<State, string> = {
    running: 'Running',
    stopped: 'Stopped',
    starting: 'Starting',
    stopping: 'Stopping',
    shutting_down: 'Shutting down',
}

const rails: Record<State, string> = {
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
 * A guest's power state, as a rail down the left edge of its row.
 *
 * The row must be `relative`, must clip (`overflow-hidden`), and must not carry
 * a border on that edge. The rail deliberately has no radius of its own and
 * takes the row's corner from the clip instead: an absolute child is laid
 * against the *padding* box, so any border shows through as a seam between the
 * rail and the row's ring -- and a `rounded-l-md` on something 4px wide clamps
 * to a 4px corner, which then juts out past the row's 8px one.
 *
 * This encodes the state in colour alone, which a colourblind viewer cannot
 * read -- so the label stays in the accessible tree (`sr-only`) and on hover
 * (`title`). Do not drop those. They are the only thing separating `stopped`
 * from `unknown` for someone who cannot tell red from grey, and those two mean
 * very different things here: see docs/node-status-plan.md, "Unknown is not
 * stopped".
 */
const PowerStateRail = ({ state, className }: Props) => {
    const label = state ? labels[state] : 'Unknown'

    return (
        <span
            title={label}
            className={cn(
                'absolute inset-y-0 left-0 w-1',
                state ? rails[state] : 'bg-muted-foreground/30',
                className
            )}
        >
            <span className={'sr-only'}>{label}</span>
        </span>
    )
}

export default PowerStateRail
