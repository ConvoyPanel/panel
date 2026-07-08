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
        <Credenza open={open} onOpenChange={close}>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Add SSH key</CredenzaTitle>
                    <CredenzaDescription>
                        Save a public key to your keychain to reuse it across
                        your servers.
                    </CredenzaDescription>
                </CredenzaHeader>

                <Form {...form}>
                    <form onSubmit={form.handleSubmit(data => trigger(data))}>
                        <CredenzaBody className={'space-y-4'}>
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
                        </CredenzaBody>
                        <CredenzaFooter className={'mt-4'}>
                            <CredenzaClose asChild>
                                <Button variant={'outline'} type={'button'}>
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <FormButton>Add key</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default SSHKeyCreateDialog
