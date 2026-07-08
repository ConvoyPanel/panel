import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'

import {
    passwordSchema,
    updateServerPassword,
    type PasswordInput,
} from '@/features/servers/security/api.ts'

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'

interface Props {
    uuid: string
}

const PasswordCard = ({ uuid }: Props) => {
    const form = useForm<PasswordInput>({
        resolver: zodResolver(passwordSchema),
        defaultValues: { password: '' },
    })

    const { mutateAsync: trigger } = useMutation({
        mutationFn: (data: PasswordInput) =>
            updateServerPassword(uuid, data.password),
        onSuccess: () => {
            toast.success('Root password updated')
            form.reset({ password: '' })
        },
        onError: e => {
            handleFormErrors(e, form.setError)
            toast.error('Failed to update password')
        },
    })

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
                <form onSubmit={form.handleSubmit(data => trigger(data))}>
                    <CardContent>
                        <InputForm
                            name={'password'}
                            label={'New password'}
                            type={'password'}
                            autoComplete={'new-password'}
                        />
                    </CardContent>
                    <CardFooter className={'flex justify-end'}>
                        <FormButton>Set password</FormButton>
                    </CardFooter>
                </form>
            </Form>
        </Card>
    )
}

export default PasswordCard
