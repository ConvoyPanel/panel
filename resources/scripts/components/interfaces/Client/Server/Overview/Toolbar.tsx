import { toast } from '@/hooks/use-toast.ts'
import { useState } from 'react'
import useSWRMutation from 'swr/mutation'

import updateState, { PowerAction } from '@/api/servers/updateState.ts'
import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'

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
    const { data: server } = useServerSWR()
    const { data } = useServerStateSWR()
    const [selectedAction, setSelectedAction] = useState<PowerAction | null>(
        null
    )
    const selectedActionData = selectedAction ? actions[selectedAction] : null

    const handlePowerAction = async (
        _: any,
        { arg: action }: { arg: PowerAction }
    ) => {
        try {
            await updateState(server!.uuid, action)

            toast({ description: selectedActionData?.toastText })
        } catch (e) {
            toast({
                // @ts-ignore
                description: `Power action failed: ${e.message}`,
                variant: 'destructive',
            })
        } finally {
            setSelectedAction(null)
        }
    }

    const { trigger, isMutating } = useSWRMutation(
        ['server.state.update', server?.uuid],
        handlePowerAction
    )

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
                    onClick={() => setSelectedAction('start')}
                >
                    Start
                </Button>
                <Button
                    variant={'outline'}
                    disabled={!data || data?.state === 'stopped'}
                    onClick={() => setSelectedAction('restart')}
                >
                    Restart
                </Button>
                <Button
                    variant={'destructiveOutline'}
                    disabled={!data || data?.state === 'stopped'}
                    onClick={() => setSelectedAction('kill')}
                >
                    Kill
                </Button>
                <Button
                    variant={'destructive'}
                    disabled={!data || data?.state === 'stopped'}
                    onClick={() => setSelectedAction('shutdown')}
                >
                    Shutdown
                </Button>
            </div>

            <Credenza
                open={Boolean(selectedAction)}
                onOpenChange={() => setSelectedAction(null)}
            >
                <CredenzaContent>
                    <CredenzaHeader>
                        <CredenzaTitle>
                            {selectedActionData?.title}
                        </CredenzaTitle>
                        <CredenzaDescription>
                            {selectedActionData?.description}
                        </CredenzaDescription>
                    </CredenzaHeader>
                    <CredenzaFooter>
                        <CredenzaClose asChild>
                            <Button
                                loading={isMutating}
                                variant={'destructive'}
                                onClick={() => trigger(selectedAction!)}
                            >
                                {selectedActionData?.buttonText}
                            </Button>
                        </CredenzaClose>
                    </CredenzaFooter>
                </CredenzaContent>
            </Credenza>
        </>
    )
}

export default Toolbar
