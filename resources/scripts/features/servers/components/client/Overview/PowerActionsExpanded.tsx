import { actions } from '@/features/servers/components/client/Overview/Toolbar.tsx'
import {
    type PowerAction,
    sendPowerCommand,
    useServer,
    useServerState,
} from '@/features/servers/detail/api.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Button } from '@/components/ui/Button'
import { toast } from '@/components/ui/Toast'

const PowerActionsExpanded = () => {
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
            if (!confirmed) return

            await sendPowerCommand(server!.uuid, action)
        } catch (e) {
            toast.add({ title: 'Power action failed', type: 'error' })
            throw e
        }
    }

    return (
        <>
            <div className={'hidden items-center gap-2 @sm:flex'}>
                <Button
                    variant={'outline'}
                    disabled={
                        !data || pending || data?.powerState === 'running'
                    }
                    onClick={() => handlePowerAction('start')}
                >
                    Start
                </Button>
                <Button
                    variant={'outline'}
                    disabled={
                        !data || pending || data?.powerState === 'stopped'
                    }
                    onClick={() => handlePowerAction('restart')}
                >
                    Restart
                </Button>
                <Button
                    variant={'destructiveOutline'}
                    disabled={
                        !data || pending || data?.powerState === 'stopped'
                    }
                    onClick={() => handlePowerAction('kill')}
                >
                    Kill
                </Button>
                <Button
                    variant={'destructive'}
                    disabled={
                        !data || pending || data?.powerState === 'stopped'
                    }
                    onClick={() => handlePowerAction('shutdown')}
                >
                    Shutdown
                </Button>
            </div>
        </>
    )
}

export default PowerActionsExpanded
