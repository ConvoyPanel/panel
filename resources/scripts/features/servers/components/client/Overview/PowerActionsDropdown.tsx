import { actions } from '@/features/servers/components/client/Overview/Toolbar.tsx'
import {
    type PowerAction,
    sendPowerCommand,
    useServer,
    useServerState,
} from '@/features/servers/detail/api.ts'
import { toast } from 'sonner'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
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

    // A power action already in flight disables all controls until it clears.
    const pending = !!data?.pendingPowerAction

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

            await sendPowerCommand(server!.uuid, action)
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
                    disabled={
                        !data || pending || data?.powerState === 'running'
                    }
                    onClick={() => handlePowerAction('start')}
                >
                    Start
                </DropdownMenuItem>
                <DropdownMenuItem
                    disabled={
                        !data || pending || data?.powerState === 'stopped'
                    }
                    onClick={() => handlePowerAction('restart')}
                >
                    Restart
                </DropdownMenuItem>
                <DropdownMenuItem
                    variant={'destructive'}
                    disabled={
                        !data || pending || data?.powerState === 'stopped'
                    }
                    onClick={() => handlePowerAction('kill')}
                >
                    Kill
                </DropdownMenuItem>
                <DropdownMenuItem
                    disabled={
                        !data || pending || data?.powerState === 'stopped'
                    }
                    onClick={() => handlePowerAction('shutdown')}
                >
                    Shutdown
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    )
}

export default PowerActionsDropdown
