import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { IconCheck, IconCopy } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'

import {
    apiKeyCreateSchema,
    apiKeyQueries,
    apiKeyScopes,
    createApiKey,
    type ApiKey,
    type ApiKeyCreateInput,
} from '@/features/account/api-keys/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'

import AuthDialog from '@/components/ui/Dialog/AuthDialog.tsx'

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
import useClipboard from '@/hooks/use-clipboard.ts'

import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import SelectForm from '@/components/ui/Forms/SelectForm'

const defaultValues: ApiKeyCreateInput = { name: '', scope: 'servers:*' }

interface Props {
    open: boolean
    onOpenChange: (open: boolean) => void
}

const ApiKeyCreateDialog = ({ open, onOpenChange }: Props) => {
    const mutate = useQueryMutator<ApiKey[]>(apiKeyQueries.all())
    const [plainTextToken, setPlainTextToken] = useState<string | null>(null)
    const { copy, copied } = useClipboard()

    const form = useForm<ApiKeyCreateInput>({
        resolver: zodResolver(apiKeyCreateSchema),
        defaultValues,
    })

    const { mutateAsync: trigger } = useMutation({
        mutationFn: createApiKey,
        onSuccess: async ({ key, plainTextToken }) => {
            await mutate(keys => (keys ? [key, ...keys] : keys), false)
            setPlainTextToken(plainTextToken)
            toast.success('API token created')
        },
        onError: e => {
            handleFormErrors(e, form.setError)
            toast.error('Failed to create token')
        },
    })

    const close = (next: boolean) => {
        if (next) return
        onOpenChange(false)
        // Reset only after the close animation so the form doesn't flash empty.
        setTimeout(() => {
            form.reset(defaultValues)
            setPlainTextToken(null)
        }, 200)
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={close}>
            <ResponsiveDialogContent>
                {plainTextToken ? (
                    <>
                        <ResponsiveDialogHeader>
                            <ResponsiveDialogTitle>API token created</ResponsiveDialogTitle>
                            <ResponsiveDialogDescription>
                                Copy your token now. For your security, it
                                won’t be shown again.
                            </ResponsiveDialogDescription>
                        </ResponsiveDialogHeader>

                        <ResponsiveDialogBody>
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
                                    aria-label={
                                        copied ? 'Token copied' : 'Copy token'
                                    }
                                    onClick={() => copy(plainTextToken)}
                                    className={'shrink-0'}
                                >
                                    {copied ? (
                                        <IconCheck className={'h-4 w-4'} />
                                    ) : (
                                        <IconCopy className={'h-4 w-4'} />
                                    )}
                                </Button>
                            </div>
                        </ResponsiveDialogBody>

                        <ResponsiveDialogFooter>
                            <ResponsiveDialogClose
                                render={
                                    <Button>Done</Button>
                                }
                            />
                        </ResponsiveDialogFooter>
                    </>
                ) : (
                    <>
                        <ResponsiveDialogHeader>
                            <ResponsiveDialogTitle>Create API token</ResponsiveDialogTitle>
                            <ResponsiveDialogDescription>
                                Generate a personal access token to use the API
                                on your behalf.
                            </ResponsiveDialogDescription>
                        </ResponsiveDialogHeader>

                        <Form {...form}>
                            <form onSubmit={form.handleSubmit(data => trigger(data))}>
                                <ResponsiveDialogBody className={'space-y-4'}>
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
                                </ResponsiveDialogBody>
                                <ResponsiveDialogFooter className={'mt-4'}>
                                    <ResponsiveDialogClose
                                        render={
                                            <Button
                                                variant={'outline'}
                                                type={'button'}
                                            >
                                                Cancel
                                            </Button>
                                        }
                                    />
                                    <FormButton>Create token</FormButton>
                                </ResponsiveDialogFooter>
                            </form>
                        </Form>
                    </>
                )}
                {/* Minting a token now needs a confirmed identity server-side —
                    the token outlives this session, so a live cookie alone must
                    not be enough. Nested inside the content on purpose: Base UI
                    gives it no backdrop of its own, so this dialog stays visible
                    underneath rather than being torn down behind the gate. */}
                <AuthDialog onCancel={() => close(false)} />
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ApiKeyCreateDialog
