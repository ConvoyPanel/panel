import { toast } from '@/hooks/use-toast.ts'

import updateState, { PowerAction } from '@/api/servers/updateState.ts'
import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import { actions } from '@/components/interfaces/Client/Server/Overview/Toolbar.tsx'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Button } from '@/components/ui/Button'


const PowerActionsExpanded = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: server } = useServerSWR()
    const { data } = useServerStateSWR()

    const handlePowerAction = async (action: PowerAction) => {
        try {
            const confirmed = await confirm({
                title: actions[action].title,
                description: actions[action].description,
            })
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
        <>
            <div className={'hidden items-center gap-2 @sm:flex'}>
                <Button
                    variant={'outline'}
                    disabled={!data || data?.state === 'running'}
                    onClick={() => handlePowerAction('start')}
                >
                    Start
                </Button>
                <Button
                    variant={'outline'}
                    disabled={!data || data?.state === 'stopped'}
                    onClick={() => handlePowerAction('restart')}
                >
                    Restart
                </Button>
                <Button
                    variant={'destructiveOutline'}
                    disabled={!data || data?.state === 'stopped'}
                    onClick={() => handlePowerAction('kill')}
                >
                    Kill
                </Button>
                <Button
                    variant={'destructive'}
                    disabled={!data || data?.state === 'stopped'}
                    onClick={() => handlePowerAction('shutdown')}
                >
                    Shutdown
                </Button>
            </div>
        </>
    )
}

export default PowerActionsExpanded
