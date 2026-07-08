import { zodResolver } from '@hookform/resolvers/zod'
import { useQueryClient } from '@tanstack/react-query'
import { IconCopy } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import {
    apiKeyQueries,
    apiKeyScopes,
    createApiKey,
    type ApiKeyScope,
} from '@/features/account/api-keys/api.ts'

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
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import SelectForm from '@/components/ui/Forms/SelectForm'

const schema = z.object({
    name: z.string().min(1).max(191),
    scope: z.enum(
        apiKeyScopes.map(s => s.value) as [ApiKeyScope, ...ApiKeyScope[]]
    ),
})

interface Props {
    open: boolean
    onOpenChange: (open: boolean) => void
}

const ApiKeyCreateDialog = ({ open, onOpenChange }: Props) => {
    const queryClient = useQueryClient()
    const [plainTextToken, setPlainTextToken] = useState<string | null>(null)

    const form = useForm<z.infer<typeof schema>>({
        resolver: zodResolver(schema),
        defaultValues: { name: '', scope: 'servers:*' },
    })

    const submit = async (data: z.infer<typeof schema>) => {
        const { plainTextToken } = await createApiKey(data.name, data.scope)

        setPlainTextToken(plainTextToken)
        await queryClient.invalidateQueries({ queryKey: apiKeyQueries.all() })
    }

    const close = (next: boolean) => {
        if (next) {
            return
        }

        onOpenChange(false)
        // Reset only after the close animation so the form doesn't flash empty.
        setTimeout(() => {
            form.reset()
            setPlainTextToken(null)
        }, 200)
    }

    const copy = async () => {
        if (!plainTextToken) {
            return
        }

        await navigator.clipboard.writeText(plainTextToken)
        toast.success('Copied token to clipboard')
    }

    return (
        <Credenza open={open} onOpenChange={close}>
            <CredenzaContent>
                {plainTextToken ? (
                    <>
                        <CredenzaHeader>
                            <CredenzaTitle>API token created</CredenzaTitle>
                            <CredenzaDescription>
                                Copy your token now. For your security, it
                                won’t be shown again.
                            </CredenzaDescription>
                        </CredenzaHeader>

                        <CredenzaBody>
                            <div
                                className={
                                    'flex items-center gap-2 rounded-md border bg-muted p-3'
                                }
                            >
                                <code
                                    className={
                                        'grow overflow-x-auto whitespace-nowrap font-mono text-sm'
                                    }
                                >
                                    {plainTextToken}
                                </code>
                                <Button
                                    variant={'ghost'}
                                    size={'icon'}
                                    onClick={copy}
                                    className={'shrink-0'}
                                >
                                    <IconCopy className={'h-4 w-4'} />
                                </Button>
                            </div>
                        </CredenzaBody>

                        <CredenzaFooter>
                            <CredenzaClose asChild>
                                <Button>Done</Button>
                            </CredenzaClose>
                        </CredenzaFooter>
                    </>
                ) : (
                    <>
                        <CredenzaHeader>
                            <CredenzaTitle>Create API token</CredenzaTitle>
                            <CredenzaDescription>
                                Generate a personal access token to use the API
                                on your behalf.
                            </CredenzaDescription>
                        </CredenzaHeader>

                        <Form {...form}>
                            <form onSubmit={form.handleSubmit(submit)}>
                                <CredenzaBody className={'space-y-4'}>
                                    <InputForm
                                        name={'name'}
                                        label={'Name'}
                                        placeholder={'e.g. Deploy CLI'}
                                    />
                                    <SelectForm
                                        name={'scope'}
                                        label={'Access'}
                                        items={apiKeyScopes.map(scope => ({
                                            value: scope.value,
                                            label: scope.label,
                                        }))}
                                    />
                                </CredenzaBody>
                                <CredenzaFooter className={'mt-4'}>
                                    <CredenzaClose asChild>
                                        <Button
                                            variant={'outline'}
                                            type={'button'}
                                        >
                                            Cancel
                                        </Button>
                                    </CredenzaClose>
                                    <FormButton>Create token</FormButton>
                                </CredenzaFooter>
                            </form>
                        </Form>
                    </>
                )}
            </CredenzaContent>
        </Credenza>
    )
}

export default ApiKeyCreateDialog
