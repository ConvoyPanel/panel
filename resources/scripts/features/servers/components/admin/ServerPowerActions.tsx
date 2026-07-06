import { useQuery } from '@tanstack/react-query'
import { toast } from 'sonner'

import {
    type PowerAction,
    serverStateQueries,
    useUpdateServerState,
} from '@/features/servers/state/api'

import { Server } from '@/types/server.ts'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'

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

// Rendered inside the row's actions dropdown. Radix only mounts the dropdown
// content while it is open, so the state query below runs on demand (when the
// menu opens) rather than polling every row in the table.
const ServerPowerActions = ({ server }: Props) => {
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: state } = useQuery(serverStateQueries.detail(server.uuid))
    const { mutateAsync } = useUpdateServerState(server.uuid)

    const isRunning = state?.state === 'running'
    const isStopped = state?.state === 'stopped'
    // Undefined (still loading) or a transitional state disables everything.
    const enabled: Record<PowerAction, boolean> = {
        start: isStopped,
        restart: isRunning,
        shutdown: isRunning,
        kill: isRunning,
    }

    const handlePowerAction = async (action: PowerAction) => {
        const confirmed = await confirm({
            title: actions[action].title,
            description: actions[action].description,
        })
        // Radix leaves pointer-events locked on the body after a dialog closes
        // over a dropdown; restore it (matches the client power dropdown).
        setTimeout(() => {
            document.body.style.pointerEvents = 'auto'
        }, 1000)
        if (!confirmed) return

        try {
            await mutateAsync(action)
            toast.success(actions[action].toastText)
        } catch (e) {
            toast.error('Power action failed')
            throw e
        }
    }

    return (
        <>
            {(Object.keys(actions) as PowerAction[]).map(action => (
                <DropdownMenuItem
                    key={action}
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
