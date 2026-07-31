import AuthSetting from '@/features/account/components/AuthSetting.tsx'
import { updatePassword } from '@/features/account/password/api.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { PASSWORD_MAX_BYTES, PASSWORD_MIN_LENGTH } from '@/utils/password.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import PasswordStrengthIndicator from '@/components/ui/Password/PasswordStrengthIndicator.tsx'
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

const schema = z
    .object({
        currentPassword: z.string().min(1, 'Current password is required'),
        password: z
            .string()
            .min(
                PASSWORD_MIN_LENGTH,
                `Password must be at least ${PASSWORD_MIN_LENGTH} characters`
            )
            .refine(
                pwd =>
                    new TextEncoder().encode(pwd).length <= PASSWORD_MAX_BYTES,
                `Password must be at most ${PASSWORD_MAX_BYTES} bytes`
            ),
        passwordConfirmation: z
            .string()
            .min(1, 'Password confirmation is required'),
    })
    .refine(data => data.password === data.passwordConfirmation, {
        message: "Passwords don't match",
        path: ['passwordConfirmation'],
    })

const PasswordChangeDialog = () => {
    const [open, setOpen] = useState(false)

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            currentPassword: '',
            password: '',
            passwordConfirmation: '',
        },
    })

    const password = form.watch('password')

    const submit = async (data: z.infer<typeof schema>) => {
        try {
            await updatePassword(data)
            form.reset()
            setOpen(false)
            toast.add({ title: 'Password updated', type: 'success' })
        } catch (error) {
            handleFormErrors(error, form.setError)

            throw error
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <AuthSetting
                        title={'Password'}
                        description={'Change your account password'}
                        onClick={() => {}}
                    />
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Change Your Password
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'space-y-2'}>
                            <InputForm
                                label={'Current Password'}
                                name={'currentPassword'}
                                autoComplete='current-password'
                                type={'password'}
                            />
                            <InputForm
                                label={'Password'}
                                name={'password'}
                                autoComplete={'new-password'}
                                type={'password'}
                            />
                            <PasswordStrengthIndicator password={password} />
                            <InputForm
                                label={'Confirm Password'}
                                name={'passwordConfirmation'}
                                autoComplete={'new-password'}
                                type={'password'}
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
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default PasswordChangeDialog
