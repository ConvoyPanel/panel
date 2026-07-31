import {
    type PowerAction,
    serverStateQueries,
} from '@/features/servers/state/api'
import { Server } from '@/types/server.ts'
import ServerController from '@/wayfinder/actions/App/Http/Controllers/Admin/ServerController'
import { IconChevronDown } from '@tabler/icons-react'
import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'

import { apiFetch } from '@/lib/api'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import { toast } from '@/components/ui/Toast'

const sendPowerCommandRoute =
    ServerController.sendPowerCommand['/api/admin/servers/{server}/power']

const labels: Record<PowerAction, string> = {
    start: 'Start',
    restart: 'Restart',
    shutdown: 'Shutdown',
    kill: 'Kill',
}

interface Props {
    servers: Server[]
    onDone?: () => void
}

// Bulk power controls rendered in the DataTable's selection bar. Issues the
// power command per selected server via the shared admin state route.
const ServerBulkPowerActions = ({ servers, onDone }: Props) => {
    const confirm = useConfirmationStore(state => state.confirm)
    const queryClient = useQueryClient()
    const [pending, setPending] = useState(false)

    const run = async (action: PowerAction) => {
        const confirmed = await confirm({
            title: `${labels[action]} ${servers.length} server(s)?`,
            description: `This will ${action} every selected server.`,
        })
        setTimeout(() => {
            document.body.style.pointerEvents = 'auto'
        }, 1000)
        if (!confirmed) return

        setPending(true)
        const results = await Promise.allSettled(
            servers.map(server =>
                apiFetch(sendPowerCommandRoute(server.uuid), {
                    body: { command: action },
                })
            )
        )
        setPending(false)

        servers.forEach(server =>
            queryClient.invalidateQueries({
                queryKey: serverStateQueries.all(server.uuid),
            })
        )

        const failed = results.filter(r => r.status === 'rejected').length
        if (failed) {
            toast.add({
                title: `${failed} of ${servers.length} power actions failed`,
                type: 'error',
            })
        } else {
            toast.add({
                title: `Queued ${action} for ${servers.length} server(s)`,
                type: 'success',
            })
        }
        onDone?.()
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant='outline' loading={pending}>
                    Power
                    <IconChevronDown className='size-4' />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align='start'>
                {(Object.keys(labels) as PowerAction[]).map(action => (
                    <DropdownMenuItem key={action} onClick={() => run(action)}>
                        {labels[action]}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    )
}

export default ServerBulkPowerActions
