import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'

import {
    createSSHKey,
    sshKeyCreateSchema,
    sshKeyQueries,
    type SSHKeyCreateInput,
} from '@/features/account/ssh-keys/api.ts'
import { SSHKey } from '@/features/account/types.ts'
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
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, TextareaForm } from '@/components/ui/Forms'

const defaultValues: SSHKeyCreateInput = { name: '', publicKey: '' }

interface Props {
    open: boolean
    onOpenChange: (open: boolean) => void
}

const SSHKeyCreateDialog = ({ open, onOpenChange }: Props) => {
    const mutate = useQueryMutator<SSHKey[]>(sshKeyQueries.all())

    const form = useForm<SSHKeyCreateInput>({
        resolver: zodResolver(sshKeyCreateSchema),
        defaultValues,
    })

    const { mutateAsync: trigger } = useMutation({
        mutationFn: createSSHKey,
        onSuccess: async key => {
            await mutate(keys => (keys ? [key, ...keys] : keys), false)
            toast.success('SSH key added')
            onOpenChange(false)
            setTimeout(() => form.reset(defaultValues), 200)
        },
        onError: e => {
            handleFormErrors(e, form.setError)
            toast.error('Failed to add key')
        },
    })

    const close = (next: boolean) => {
        if (next) return
        onOpenChange(false)
        setTimeout(() => form.reset(defaultValues), 200)
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={close}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Add SSH key</ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Save a public key to your keychain to reuse it across
                        your servers.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>

                <Form {...form}>
                    <form onSubmit={form.handleSubmit(data => trigger(data))}>
                        <ResponsiveDialogBody className={'space-y-4'}>
                            <InputForm
                                name={'name'}
                                label={'Name'}
                                placeholder={'e.g. Work laptop'}
                            />
                            <TextareaForm
                                name={'publicKey'}
                                label={'Public key'}
                                placeholder={'ssh-ed25519 AAAA… user@host'}
                            />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Add key</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
                {/* Adding a key now needs a confirmed identity server-side: an
                    SSH key grants server access and outlives this session, so a
                    live cookie alone must not be enough. Nested inside the
                    content so Base UI gives it no backdrop of its own. */}
                <AuthDialog onCancel={() => close(false)} />
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default SSHKeyCreateDialog
