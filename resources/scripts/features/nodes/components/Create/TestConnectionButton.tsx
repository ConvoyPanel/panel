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
import { toast } from 'sonner'

import { testConnection } from '@/features/nodes/api.ts'
import { connectionErrorCopy } from '@/features/nodes/components/Create/connection-errors.ts'

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

const TestConnectionButton = () => {
    const form = useFormContext()
    const [result, setResult] = useState<ConnectionResult | null>(null)
    const [open, setOpen] = useState(false)

    const [state, handle] = useAsyncFunction(async () => {
        try {
            setResult(null)
            // @ts-expect-error
            const result = await testConnection(form.getValues())
            setResult(result)
            if (!result.success) {
                setOpen(true)
            } else {
                form.setValue('socketCount', result.data!.cpu.socketCount)
                form.setValue('coreCount', result.data!.cpu.coreCount)
                form.setValue('cpuCount', result.data!.cpu.cpuCount)
                form.setValue(
                    'memory',
                    Math.floor(result.data!.memory.total / (1024 * 1024))
                )
            }
        } catch (e) {
            toast.error('Failed to test connection')
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
                                    className={'size-5 text-muted-foreground'}
                                />

                                <p className={'text-sm text-muted-foreground'}>
                                    Connection succeeded
                                </p>
                            </>
                        ) : (
                            <>
                                <IconX className={'size-5 text-destructive'} />
                                <p className={'text-sm text-destructive'}>
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
                        <ResponsiveDialogTitle>{errorCopy.title}</ResponsiveDialogTitle>
                        <ResponsiveDialogDescription>
                            {errorCopy.description}
                        </ResponsiveDialogDescription>
                    </ResponsiveDialogHeader>
                    <ResponsiveDialogBody>
                        <details className={'group/details'}>
                            <summary
                                className={
                                    'flex cursor-pointer list-none items-center gap-1 text-sm text-muted-foreground hover:text-foreground [&::-webkit-details-marker]:hidden'
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
                                    'mt-2 font-mono text-xs leading-relaxed break-words text-muted-foreground'
                                }
                            >
                                {result?.errorMessage}
                            </p>
                        </details>
                    </ResponsiveDialogBody>
                    <ResponsiveDialogFooter>
                        <ResponsiveDialogClose
                            render={
                                <Button variant={'outline'}>Close</Button>
                            }
                        />
                        <Button onClick={askChatGPT}>Ask ChatGPT</Button>
                    </ResponsiveDialogFooter>
                </ResponsiveDialogContent>
            </ResponsiveDialog>
        </>
    )
}

export default TestConnectionButton
