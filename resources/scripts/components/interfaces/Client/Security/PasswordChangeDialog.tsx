import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import { updatePassword } from '@/features/account/password/api.ts'

import AuthSetting from '@/components/interfaces/Client/Security/AuthSetting.tsx'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import PasswordStrengthIndicator from '@/components/ui/Password/PasswordStrengthIndicator.tsx'

const schema = z
    .object({
        currentPassword: z.string().min(1, 'Current password is required'),
        password: z.string().min(12, 'Password must be at least 12 characters'),
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
            toast.success('Password updated')
        } catch (error) {
            handleFormErrors(error, form.setError)

            throw error
        }
    }

    return (
        <Credenza open={open} onOpenChange={setOpen}>
            <CredenzaTrigger asChild>
                <AuthSetting
                    title={'Password'}
                    description={'Change your account password'}
                    onClick={() => {}}
                />
            </CredenzaTrigger>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>Change Your Password</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <CredenzaBody className={'space-y-2'}>
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
                        </CredenzaBody>
                        <CredenzaFooter className={'mt-4'}>
                            <CredenzaClose asChild>
                                <Button variant={'outline'} type={'button'}>
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <FormButton>Confirm</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default PasswordChangeDialog
