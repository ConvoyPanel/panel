import { toast } from 'sonner'

import {
    updateState,
    type PowerAction,
    useServerState,
    useServer,
} from '@/features/servers/detail/api.ts'

import { actions } from '@/features/servers/components/client/Overview/Toolbar.tsx'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'

const PowerActionsDropdown = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: server } = useServer()
    const { data } = useServerState()

    const handlePowerAction = async (action: PowerAction) => {
        try {
            const confirmed = await confirm({
                title: actions[action].title,
                description: actions[action].description,
            })
            setTimeout(() => {
                document.body.style.pointerEvents = 'auto'
            }, 1000)
            if (!confirmed) return

            await updateState(server!.uuid, action)
            toast.success(actions[action].toastText)
        } catch (e) {
            toast.error('Power action failed')
            throw e
        }
    }

    return (
        <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
                <Button className={'block @sm:hidden'}>Power Actions</Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent>
                <DropdownMenuItem
                    disabled={!data || data?.state === 'running'}
                    onClick={() => handlePowerAction('start')}
                >
                    Start
                </DropdownMenuItem>
                <DropdownMenuItem
                    disabled={!data || data?.state === 'stopped'}
                    onClick={() => handlePowerAction('restart')}
                >
                    Restart
                </DropdownMenuItem>
                <DropdownMenuItem
                    disabled={!data || data?.state === 'stopped'}
                    onClick={() => handlePowerAction('kill')}
                >
                    Kill
                </DropdownMenuItem>
                <DropdownMenuItem
                    disabled={!data || data?.state === 'stopped'}
                    onClick={() => handlePowerAction('shutdown')}
                >
                    Shutdown
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    )
}

export default PowerActionsDropdown
