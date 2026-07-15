import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { IconCheck, IconCopy, IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'

import { createToken, tokenSchema, type TokenInput } from '@/features/tokens/api.ts'
import {
    resourceLabels,
    TOKEN_RESOURCES,
    type PaginatedApiKeys,
} from '@/features/tokens/types.ts'
import useClipboard from '@/hooks/use-clipboard.ts'
import type { Mutator } from '@/types/query.ts'

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
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm } from '@/components/ui/Forms'
import SelectForm from '@/components/ui/Forms/SelectForm'

const scopeItems = [
    { value: 'none', label: 'No access' },
    { value: 'read', label: 'Read only' },
    { value: 'write', label: 'Read & write' },
]

const defaultValues: TokenInput = {
    name: '',
    fullAccess: true,
    scopes: Object.fromEntries(
        TOKEN_RESOURCES.map(resource => [resource, 'none'])
    ) as TokenInput['scopes'],
}

interface Props {
    mutate: Mutator<PaginatedApiKeys>
}

const CreateTokenModal = ({ mutate }: Props) => {
    const [open, setOpen] = useState(false)
    const [plainTextToken, setPlainTextToken] = useState<string | null>(null)
    const { copy, copied } = useClipboard()

    const form = useForm<TokenInput>({
        resolver: zodResolver(tokenSchema),
        defaultValues,
    })

    const fullAccess = form.watch('fullAccess')

    const { mutateAsync: trigger } = useMutation({
        mutationFn: createToken,
        onSuccess: async token => {
            await mutate(data => {
                if (!data) return
                return { ...data, items: [token, ...data.items] }
            }, false)

            setPlainTextToken(token.plainTextToken ?? '')
            toast.success('API token created')
        },
        onError: e => {
            handleFormErrors(e, form.setError)
            toast.error('Failed to create token')
        },
    })

    const onOpenChange = (next: boolean) => {
        setOpen(next)
        if (!next) {
            // Reset after the close animation so the form doesn't flash.
            setTimeout(() => {
                form.reset(defaultValues)
                setPlainTextToken(null)
            }, 200)
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={onOpenChange}>
            <ResponsiveDialogTrigger
                render={
                    <Button>
                        <IconPlus className={'size-4'} /> Create token
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                {plainTextToken ? (
                    <>
                        <ResponsiveDialogHeader>
                            <ResponsiveDialogTitle>Token created</ResponsiveDialogTitle>
                            <ResponsiveDialogDescription>
                                Copy this token now. For security, it won’t be
                                shown again.
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
                                A panel-wide token for the application API.
                            </ResponsiveDialogDescription>
                        </ResponsiveDialogHeader>
                        <Form {...form}>
                            <form onSubmit={form.handleSubmit(data => trigger(data))}>
                                <ResponsiveDialogBody className={'space-y-4'}>
                                    <InputForm
                                        name={'name'}
                                        label={'Name'}
                                        placeholder={'e.g. Deploy bot'}
                                    />
                                    <CheckboxForm
                                        name={'fullAccess'}
                                        label={'Full access'}
                                        description={
                                            'Grant every ability. Turn off to scope the token per resource.'
                                        }
                                    />
                                    {!fullAccess &&
                                        TOKEN_RESOURCES.map(resource => (
                                            <SelectForm
                                                key={resource}
                                                name={`scopes.${resource}`}
                                                label={resourceLabels[resource]}
                                                items={scopeItems}
                                            />
                                        ))}
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
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default CreateTokenModal
