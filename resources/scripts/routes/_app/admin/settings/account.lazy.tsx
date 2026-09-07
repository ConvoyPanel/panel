import {
    accountSettingsQuery,
    accountSettingsSchema,
    updateAccountSettings,
    useAccountSettings,
} from '@/features/settings/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { SwitchForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

const AccountSettingsPage = () => {
    const { data: settings, isLoading } = useAccountSettings()
    const mutateSettings = useQueryMutator(accountSettingsQuery().queryKey)

    const form = useForm<z.infer<typeof accountSettingsSchema>>({
        resolver: zodResolver(accountSettingsSchema),
        defaultValues: {
            allowNameChange: true,
            allowEmailChange: true,
            allowPasswordChange: true,
        },
    })

    useEffect(() => {
        if (!settings) return

        form.reset({
            allowNameChange: settings.allowNameChange,
            allowEmailChange: settings.allowEmailChange,
            allowPasswordChange: settings.allowPasswordChange,
        })
    }, [form, settings])

    const { mutateAsync: save } = useMutation({
        mutationFn: updateAccountSettings,
        onSuccess: async updated => {
            await mutateSettings(() => updated)
            toast.add({ title: 'Account settings updated', type: 'success' })
        },
    })

    const submit = async (data: z.infer<typeof accountSettingsSchema>) => {
        try {
            await save(data)
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({
                title: 'Failed to update account settings',
                type: 'error',
            })
            console.error(e)
        }
    }

    if (isLoading) {
        return (
            <>
                <Heading>Accounts</Heading>
                <Skeleton className={'h-64'} />
            </>
        )
    }

    return (
        <div className={'@container space-y-4'}>
            <Heading>Accounts</Heading>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Card>
                        <CardHeader>
                            <CardTitle>What users can change</CardTitle>
                        </CardHeader>
                        {/* Divided rows rather than a box per switch: three
                            bordered cards nested inside a bordered card spend
                            a full content column on one word each. Each row
                            carries what turning it off costs, which is the
                            question the switch raises. */}
                        <CardContent className={'divide-y'}>
                            <SwitchForm
                                name={'allowNameChange'}
                                label={'Display name'}
                                description={
                                    'Shown on their account and beside their actions in the audit log.'
                                }
                                formItemProps={{
                                    className: 'py-3.5 first:pt-0 last:pb-0',
                                }}
                            />
                            <SwitchForm
                                name={'allowEmailChange'}
                                label={'Email address'}
                                description={
                                    'The address they sign in with. Changing it always requires confirming their identity first.'
                                }
                                formItemProps={{
                                    className: 'py-3.5 first:pt-0 last:pb-0',
                                }}
                            />
                            <SwitchForm
                                name={'allowPasswordChange'}
                                label={'Password'}
                                description={
                                    'There is no password reset flow, so turning this off makes their current password permanent.'
                                }
                                formItemProps={{
                                    className: 'py-3.5 first:pt-0 last:pb-0',
                                }}
                            />
                        </CardContent>
                    </Card>

                    <div className={'mt-4 flex justify-end'}>
                        <FormButton className={'flex'}>
                            Save changes <IconCheck className={'size-4'} />
                        </FormButton>
                    </div>
                </form>
            </Form>
        </div>
    )
}

export const Route = createLazyFileRoute('/_app/admin/settings/account')({
    component: AccountSettingsPage,
})
