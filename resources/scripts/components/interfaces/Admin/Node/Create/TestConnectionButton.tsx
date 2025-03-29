import useAsyncFunction from '@/hooks/use-async-function.ts'
import { ConnectionResult } from '@/types/node.ts'
import { IconCheck, IconX } from '@tabler/icons-react'
import { useState } from 'react'
import { useFormContext } from 'react-hook-form'

import testConnection from '@/api/admin/nodes/testConnection.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
} from '@/components/ui/Credenza'
import { toast } from '@/components/ui/Toast'

const TestConnectionButton = () => {
    const form = useFormContext()
    const [result, setResult] = useState<ConnectionResult | null>(null)
    const [open, setOpen] = useState(false)

    const [state, handle] = useAsyncFunction(async () => {
        try {
            // @ts-expect-error
            const result = await testConnection(form.getValues())
            setResult(result)
            if (!result.success) {
                setOpen(true)
            }
        } catch (e) {
            toast({
                description: 'Failed to test connection',
                variant: 'destructive',
            })
            console.error(e)
        }
    })

    const askChatGPT = () => {
        const url = new URL('https://chat.openai.com/');
        url.searchParams.set('q', result?.errorMessage ?? '');
        window.open(url.toString(), '_blank');
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
                <Button
                    type={'button'}
                    onClick={handle}
                    loading={state.loading}
                >
                    Test connection
                </Button>
            </div>

            <Credenza open={open} onOpenChange={setOpen}>
                <CredenzaContent>
                    <CredenzaHeader>
                        <CredenzaTitle>Connection failed</CredenzaTitle>
                        <CredenzaDescription>
                            Error code: {result?.errorCode}
                        </CredenzaDescription>
                    </CredenzaHeader>
                    <CredenzaBody>
                        <p className={'text-sm text-muted-foreground'}>
                            {result?.errorMessage}
                        </p>
                    </CredenzaBody>
                    <CredenzaFooter>
                        <CredenzaClose asChild>
                            <Button variant={'outline'}>Close</Button>
                        </CredenzaClose>
                        <Button onClick={askChatGPT}>
                            Ask ChatGPT
                        </Button>
                    </CredenzaFooter>
                </CredenzaContent>
            </Credenza>
        </>
    )
}

export default TestConnectionButton
