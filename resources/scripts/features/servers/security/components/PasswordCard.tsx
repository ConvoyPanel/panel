import { useIdentityConfirmed } from '@/features/auth/identity/api.ts'
import {
    type PasswordInput,
    passwordSchema,
    updateServerPassword,
} from '@/features/servers/security/api.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import AuthDialog from '@/components/ui/Dialog/AuthDialog.tsx'
import { Form } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'

interface Props {
    uuid: string
}

const defaultValues: PasswordInput = { password: '', passwordConfirmation: '' }

const PasswordCard = ({ uuid }: Props) => {
    const form = useForm<PasswordInput>({
        resolver: zodResolver(passwordSchema),
        defaultValues,
    })

    // The server gates this behind a confirmed identity (UpdateAuthSettingsRequest),
    // so ask first rather than letting the submit 403 with only a toast to show for it.
    const identityConfirmed = useIdentityConfirmed()
    const [pending, setPending] = useState<PasswordInput | null>(null)

    const { mutateAsync: trigger, isPending } = useMutation({
        mutationFn: (data: PasswordInput) =>
            updateServerPassword(uuid, data.password),
        onSuccess: () => {
            toast.add({ title: 'Root password updated', type: 'success' })
            form.reset(defaultValues)
        },
        onError: e => {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to update password', type: 'error' })
        },
    })

    // Held back until the gate below reports a confirmed identity. Confirming closes
    // <AuthDialog> on its own, so this is what resumes the submit it interrupted.
    useEffect(() => {
        if (!pending || !identityConfirmed) return

        const data = pending
        setPending(null)
        // onError already surfaces the failure; nothing awaits this one.
        void trigger(data).catch(() => {})
    }, [pending, identityConfirmed])

    const submit = async (data: PasswordInput) => {
        if (!identityConfirmed) {
            setPending(data)

            return
        }

        await trigger(data)
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Root Password</CardTitle>
                <CardDescription>
                    Set a new password for the server’s default user. Applied on
                    the next boot via cloud-init.
                </CardDescription>
            </CardHeader>
            <Form {...form}>
                <form
                    // The form sits between the Card's flex column and its
                    // parts, so it has to carry the column itself — otherwise a
                    // card stretched to a taller neighbour leaves the surplus
                    // dead below the footer instead of under the input.
                    className={'flex flex-1 flex-col'}
                    onSubmit={form.handleSubmit(submit)}
                >
                    <CardContent className={'flex-1 space-y-2'}>
                        <InputForm
                            name={'password'}
                            label={'New password'}
                            type={'password'}
                            autoComplete={'new-password'}
                        />
                        <InputForm
                            name={'passwordConfirmation'}
                            label={'Confirm password'}
                            type={'password'}
                            autoComplete={'new-password'}
                        />
                    </CardContent>
                    <CardFooter className={'flex justify-end'}>
                        <Button
                            type={'submit'}
                            // Not FormButton: the submit returns as soon as the gate
                            // goes up, and the request it is waiting to make still has
                            // to read as in flight.
                            loading={
                                form.formState.isSubmitting ||
                                pending !== null ||
                                isPending
                            }
                        >
                            Set password
                        </Button>
                    </CardFooter>
                </form>
            </Form>

            {/* Mounted only while a submit is waiting on it — <AuthDialog> opens
                itself whenever identity is unconfirmed, so leaving it mounted would
                raise the gate on anyone who merely opened this page. */}
            {pending !== null && (
                <AuthDialog onCancel={() => setPending(null)} />
            )}
        </Card>
    )
}

export default PasswordCard
