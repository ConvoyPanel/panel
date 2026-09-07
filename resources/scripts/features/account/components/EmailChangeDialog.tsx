import { emailSchema, updateEmail } from '@/features/account/profile/api.ts'
import { currentUserQueries, useUser } from '@/features/auth/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { AuthenticatedUser } from '@/types/user.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { type ReactElement, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import AuthDialog from '@/components/ui/Dialog/AuthDialog.tsx'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

interface Props {
    trigger: ReactElement
}

const EmailChangeDialog = ({ trigger }: Props) => {
    const [open, setOpen] = useState(false)
    const { data: user } = useUser()
    const mutate = useQueryMutator<AuthenticatedUser>(currentUserQueries.all())

    const form = useForm({
        resolver: zodResolver(emailSchema),
        defaultValues: { email: '' },
    })

    const submit = async (data: z.infer<typeof emailSchema>) => {
        try {
            const updated = await updateEmail(data)

            await mutate(() => updated)
            form.reset()
            setOpen(false)
            toast.add({ title: 'Email updated', type: 'success' })
        } catch (e) {
            handleFormErrors(e, form.setError)

            throw e
        }
    }

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={next => {
                setOpen(next)
                if (next) form.reset({ email: user?.email ?? '' })
            }}
        >
            <ResponsiveDialogTrigger render={trigger} />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Change Your Email
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody>
                            <InputForm
                                label={'Email'}
                                name={'email'}
                                type={'email'}
                                autoComplete={'email'}
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
                            <FormButton>Confirm</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>

                {/* The endpoint is behind RequireIdentityConfirmation, so the
                    gate mounts inside this dialog the way the credential
                    dialogs do — Base UI nests it without a second backdrop. */}
                <AuthDialog onCancel={() => setOpen(false)} />
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default EmailChangeDialog
