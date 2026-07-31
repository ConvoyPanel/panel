import {
    type PowerAction,
    serverStateQueries,
    useSendPowerCommand,
} from '@/features/servers/state/api'
import { Server } from '@/types/server.ts'
import { useQuery } from '@tanstack/react-query'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import { toast } from '@/components/ui/Toast'

const actions: Record<
    PowerAction,
    { label: string; toastText: string; title: string; description: string }
> = {
    start: {
        label: 'Start',
        toastText: 'Server queued to start',
        title: 'Start server?',
        description: 'This action will start the server.',
    },
    restart: {
        label: 'Restart',
        toastText: 'Server queued to restart',
        title: 'Restart server?',
        description:
            'This will restart the server. Any unsaved work on the guest may be lost.',
    },
    shutdown: {
        label: 'Shutdown',
        toastText: 'Server queued to shut down',
        title: 'Shutdown server?',
        description:
            'This will gracefully power off the server. Unsaved work on the guest may be lost.',
    },
    kill: {
        label: 'Kill',
        toastText: 'Server queued to be killed',
        title: 'Kill server?',
        description:
            'This immediately stops the server without a graceful shutdown, which can cause data loss.',
    },
}

interface Props {
    server: Server
}

// Rendered inside the row's actions dropdown. The menu only mounts its popup
// content while it is open, so the state query below runs on demand (when the
// menu opens) rather than polling every row in the table.
const ServerPowerActions = ({ server }: Props) => {
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: state } = useQuery(serverStateQueries.detail(server.uuid))
    const { mutateAsync } = useSendPowerCommand(server.uuid)

    const isRunning = state?.powerState === 'running'
    const isStopped = state?.powerState === 'stopped'
    const pending = state?.pendingPowerAction ?? null
    // Undefined (still loading), a transitional state, or a power action already
    // in flight disables everything.
    const enabled: Record<PowerAction, boolean> = {
        start: isStopped && !pending,
        restart: isRunning && !pending,
        shutdown: isRunning && !pending,
        kill: isRunning && !pending,
    }

    const handlePowerAction = async (action: PowerAction) => {
        const confirmed = await confirm({
            title: actions[action].title,
            description: actions[action].description,
        })
        if (!confirmed) return

        try {
            await mutateAsync(action)
            toast.add({ title: actions[action].toastText, type: 'success' })
        } catch (e) {
            toast.add({ title: 'Power action failed', type: 'error' })
            throw e
        }
    }

    return (
        <>
            {pending && (
                <DropdownMenuItem disabled>
                    {actions[pending.command as PowerAction]?.label ??
                        'Power action'}{' '}
                    in progress…
                </DropdownMenuItem>
            )}
            {(Object.keys(actions) as PowerAction[]).map(action => (
                <DropdownMenuItem
                    key={action}
                    variant={action === 'kill' ? 'destructive' : 'default'}
                    disabled={!enabled[action]}
                    onClick={() => handlePowerAction(action)}
                >
                    {actions[action].label}
                </DropdownMenuItem>
            ))}
        </>
    )
}

export default ServerPowerActions
