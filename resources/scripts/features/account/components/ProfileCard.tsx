import AuthSetting from '@/features/account/components/AuthSetting.tsx'
import EmailChangeDialog from '@/features/account/components/EmailChangeDialog.tsx'
import { profileSchema, updateProfile } from '@/features/account/profile/api.ts'
import { currentUserQueries, useUser } from '@/features/auth/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { AuthenticatedUser } from '@/types/user.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import { toast } from '@/components/ui/Toast'

const ProfileCard = () => {
    const { data: user } = useUser()
    const mutate = useQueryMutator<AuthenticatedUser>(currentUserQueries.all())

    const form = useForm({
        resolver: zodResolver(profileSchema),
        defaultValues: { name: '' },
    })

    useEffect(() => {
        if (!user) return

        form.reset({ name: user.name })
    }, [form, user])

    const { mutateAsync: save } = useMutation({
        mutationFn: updateProfile,
        onSuccess: async updated => {
            await mutate(() => updated)
            toast.add({ title: 'Profile updated', type: 'success' })
        },
    })

    const submit = async (data: z.infer<typeof profileSchema>) => {
        try {
            await save(data)
        } catch (e) {
            handleFormErrors(e, form.setError)

            throw e
        }
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Details</CardTitle>
            </CardHeader>
            <CardContent className={'space-y-4'}>
                <Form {...form}>
                    <form
                        onSubmit={form.handleSubmit(submit)}
                        className={'space-y-3'}
                    >
                        <InputForm name={'name'} label={'Display name'} />
                        <div className={'flex justify-end'}>
                            <FormButton>Save</FormButton>
                        </div>
                    </form>
                </Form>

                {/* Its own flow rather than a second field in the form above:
                    the address a password reset goes to is not something a live
                    cookie alone may change, so the endpoint behind it is gated
                    on a confirmed identity. */}
                <EmailChangeDialog
                    trigger={
                        <AuthSetting
                            title={'Email'}
                            description={user?.email ?? ''}
                            onClick={() => {}}
                        />
                    }
                />
            </CardContent>
        </Card>
    )
}

export default ProfileCard
