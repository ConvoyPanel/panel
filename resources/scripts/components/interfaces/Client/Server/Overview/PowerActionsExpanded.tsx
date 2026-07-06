import { toast } from 'sonner'

import updateState, { PowerAction } from '@/api/servers/updateState.ts'
import useServerState from '@/api/servers/use-server-state.ts'
import useServer from '@/api/servers/use-server.ts'

import { actions } from '@/components/interfaces/Client/Server/Overview/Toolbar.tsx'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Button } from '@/components/ui/Button'

const PowerActionsExpanded = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: server } = useServer()
    const { data } = useServerState()

    const handlePowerAction = async (action: PowerAction) => {
        try {
            const confirmed = await confirm({
                title: actions[action].title,
                description: actions[action].description,
            })
            if (!confirmed) return

            await updateState(server!.uuid, action)
            toast.success(actions[action].toastText)
        } catch (e) {
            toast.error('Power action failed')
            throw e
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
