import {
    type ConnectionTestForm,
    testConnection,
} from '@/features/nodes/api.ts'
import { connectionErrorCopy } from '@/features/nodes/connection-errors.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { ConnectionResult } from '@/types/node.ts'
import {
    IconCheck,
    IconChevronRight,
    IconPlugConnected,
    IconX,
} from '@tabler/icons-react'
import { useState } from 'react'
import { useFormContext } from 'react-hook-form'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const TestConnectionButton = ({ nodeId }: { nodeId?: number }) => {
    const form = useFormContext<ConnectionTestForm>()
    const [result, setResult] = useState<ConnectionResult | null>(null)
    const [open, setOpen] = useState(false)

    const [state, handle] = useAsyncFunction(async () => {
        try {
            setResult(null)
            const result = await testConnection(form.getValues(), nodeId)
            setResult(result)
            if (!result.success) {
                setOpen(true)
            } else {
                /* shouldDirty because the settings page gates Save on the form
                   being dirty: without it the test writes the host's real
                   numbers into the fields while Save stays disabled and no
                   Discard appears, so what is on screen can neither be saved
                   nor reverted. */
                const filled = { shouldDirty: true }

                form.setValue(
                    'socketCount',
                    result.data!.cpu.socketCount,
                    filled
                )
                form.setValue('coreCount', result.data!.cpu.coreCount, filled)
                form.setValue('cpuCount', result.data!.cpu.cpuCount, filled)
                form.setValue(
                    'memory',
                    Math.floor(result.data!.memory.total / (1024 * 1024)),
                    filled
                )
            }
        } catch (e) {
            toast.add({ title: 'Failed to test connection', type: 'error' })
            console.error(e)
        }
    })

    const errorCopy = connectionErrorCopy(result?.errorCode ?? null)

    const askChatGPT = () => {
        const url = new URL('https://chat.openai.com/')
        url.searchParams.set('q', result?.errorMessage ?? '')
        window.open(url.toString(), '_blank')
    }

    return (
        <>
            <div className={'flex items-center justify-end space-x-4'}>
                {result && (
                    <div className={'flex items-center space-x-1'}>
                        {result.success ? (
                            <>
                                <IconCheck
                                    className={'text-muted-foreground size-5'}
                                />

                                <p className={'text-muted-foreground text-sm'}>
                                    Connection succeeded
                                </p>
                            </>
                        ) : (
                            <>
                                <IconX className={'text-destructive size-5'} />
                                <p className={'text-destructive text-sm'}>
                                    Connection failed
                                </p>
                            </>
                        )}
                    </div>
                )}
                {/* The icon keeps the icon slot occupied so swapping in the
                    spinner doesn't resize the button mid-click. */}
                <Button
                    type={'button'}
                    onClick={handle}
                    icon={<IconPlugConnected className={'size-4'} />}
                    loading={state.loading}
                >
                    Test connection
                </Button>
            </div>

            <ResponsiveDialog open={open} onOpenChange={setOpen}>
                <ResponsiveDialogContent>
                    <ResponsiveDialogHeader>
                        <ResponsiveDialogTitle>
                            {errorCopy.title}
                        </ResponsiveDialogTitle>
                        <ResponsiveDialogDescription>
                            {errorCopy.description}
                        </ResponsiveDialogDescription>
                    </ResponsiveDialogHeader>
                    <ResponsiveDialogBody>
                        <details className={'group/details'}>
                            <summary
                                className={
                                    'text-muted-foreground hover:text-foreground flex cursor-pointer list-none items-center gap-1 text-sm [&::-webkit-details-marker]:hidden'
                                }
                            >
                                <IconChevronRight
                                    className={
                                        'size-3.5 transition-transform group-open/details:rotate-90'
                                    }
                                />
                                Technical details
                            </summary>
                            <p
                                className={
                                    'text-muted-foreground mt-2 font-mono text-xs leading-relaxed break-words'
                                }
                            >
                                {result?.errorMessage}
                            </p>
                        </details>
                    </ResponsiveDialogBody>
                    <ResponsiveDialogFooter>
                        <ResponsiveDialogClose
                            render={<Button variant={'outline'}>Close</Button>}
                        />
                        <Button onClick={askChatGPT}>Ask ChatGPT</Button>
                    </ResponsiveDialogFooter>
                </ResponsiveDialogContent>
            </ResponsiveDialog>
        </>
    )
}

export default TestConnectionButton
