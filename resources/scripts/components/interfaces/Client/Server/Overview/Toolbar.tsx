import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import { Button } from '@/components/ui/Button'


const actions = {
    start: {
        buttonText: 'Start server',
        toastText: 'Server queued to start',
        title: 'Start server?',
        description: 'This action will start your server.',
    },
    restart: {
        buttonText: 'Restart server',
        toastText: 'Server queued to restart',
        title: 'Restart server?',
        description: 'Make sure to save your work before restarting.',
    },
    kill: {
        buttonText: 'Kill server',
        toastText: 'Server queued to be killed',
        title: 'Kill server?',
        description: 'Data may be lost if you kill your server.',
    },
    shutdown: {
        buttonText: 'Shutdown server',
        toastText: 'Server queued to be shutdown',
        title: 'Shutdown server?',
        description:
            'Make sure to save your work before shutting down your server.',
    },
}

const Toolbar = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: server } = useServerSWR()
    const { data } = useServerStateSWR()
    // const [selectedAction, setSelectedAction] = useState<PowerAction | null>(
    //     null
    // )
    // const selectedActionData = selectedAction ? actions[selectedAction] : null
    //
    // const handlePowerAction = async (
    //     _: any,
    //     { arg: action }: { arg: PowerAction }
    // ) => {
    //     try {
    //         await updateState(server!.uuid, action)
    //
    //         toast({ description: selectedActionData?.toastText })
    //     } catch (e) {
    //         toast({
    //             // @ts-ignore
    //             description: `Power action failed: ${e.message}`,
    //             variant: 'destructive',
    //         })
    //     } finally {
    //         setSelectedAction(null)
    //     }
    // }

    // const { trigger, isMutating } = useSWRMutation(
    //     ['server.state.update', server?.uuid],
    //     handlePowerAction
    // )

    const test = async () => {
        console.log(await confirm({ title: 'test' }))
    }

    return (
        <>
            <div
                className={
                    'grid grid-cols-2 items-center justify-end gap-2 sm:flex'
                }
            >
                <Button
                    variant={'outline'}
                    disabled={!data || data?.state === 'running'}
                >
                    Start
                </Button>
                <Button
                    variant={'outline'}
                    disabled={!data || data?.state === 'stopped'}
                    onClick={test}
                >
                    Restart
                </Button>
                <Button
                    variant={'destructiveOutline'}
                    disabled={!data || data?.state === 'stopped'}
                >
                    Kill
                </Button>
                <Button
                    variant={'destructive'}
                    disabled={!data || data?.state === 'stopped'}
                >
                    Shutdown
                </Button>
            </div>
        </>
    )
}

export default Toolbar
