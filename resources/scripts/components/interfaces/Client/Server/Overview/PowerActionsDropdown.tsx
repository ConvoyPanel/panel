import updateState, { PowerAction } from '@/api/servers/updateState.ts'
import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import { actions } from '@/components/interfaces/Client/Server/Overview/Toolbar.tsx'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import { toast } from '@/components/ui/Toast/use-toast.ts'


const PowerActionsDropdown = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: server } = useServerSWR()
    const { data } = useServerStateSWR()

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
            toast({ description: actions[action].toastText })
        } catch (e) {
            toast({
                // @ts-ignore
                description: `Power action failed: ${e.message}`,
                variant: 'destructive',
            })
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
