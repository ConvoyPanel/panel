import { Badge } from '@/components/ui/Badge'

type State = App.Enums.Server.PowerState

const labels: Record<State, string> = {
    running: 'Running',
    stopped: 'Stopped',
    starting: 'Starting',
    stopping: 'Stopping',
    shutting_down: 'Shutting down',
}

const PowerStateBadge = ({ state }: { state: State | null }) => (
    <Badge variant={state === 'running' ? 'default' : 'secondary'}>
        {state ? labels[state] : 'Unknown'}
    </Badge>
)

export default PowerStateBadge
