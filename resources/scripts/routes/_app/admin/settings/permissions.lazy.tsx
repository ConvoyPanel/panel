import {
    permissionSettingsQuery,
    permissionSettingsSchema,
    updatePermissionSettings,
    usePermissionSettings,
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

const countOf = (count: number, noun: string) =>
    `${count} ${noun}${count === 1 ? '' : 's'}`

type Values = z.infer<typeof permissionSettingsSchema>

const PermissionSettingsPage = () => {
    const { data: settings, isLoading } = usePermissionSettings()
    const mutateSettings = useQueryMutator(permissionSettingsQuery().queryKey)

    const form = useForm<Values>({
        resolver: zodResolver(permissionSettingsSchema),
        defaultValues: { allowGuestAccounts: false },
    })

    useEffect(() => {
        if (!settings) return

        form.reset({ allowGuestAccounts: settings.allowGuestAccounts })
    }, [form, settings])

    const { mutateAsync: save } = useMutation({
        mutationFn: updatePermissionSettings,
        onSuccess: async updated => {
            await mutateSettings(() => updated)
            toast.add({ title: 'Sharing settings updated', type: 'success' })
        },
    })

    const submit = async (data: Values) => {
        try {
            await save(data)
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({
                title: 'Failed to update sharing settings',
                type: 'error',
            })
            console.error(e)
        }
    }

    if (isLoading) {
        return (
            <>
                <Heading>Sharing</Heading>
                <Skeleton className={'h-48'} />
            </>
        )
    }

    const guests = settings?.guestAccountCount ?? 0

    return (
        <div className={'@container space-y-4'}>
            <Heading>Sharing</Heading>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Guest accounts</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {/* The consequence, with the real number in it, attached to the
                                control it governs: turning this off is not just a block on new
                                guests, it signs the existing ones out. */}
                            <SwitchForm
                                name={'allowGuestAccounts'}
                                label={'Let customers share with new people'}
                                description={
                                    guests > 0
                                        ? `Sharing a server with an address that has no account creates one. Turning this off also signs out the ${countOf(guests, 'guest account')} that already ${guests === 1 ? 'exists' : 'exist'}.`
                                        : 'Sharing a server with an address that has no account creates one. Turning this off also signs out any guest account that already exists.'
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

export const Route = createLazyFileRoute('/_app/admin/settings/permissions')({
    component: PermissionSettingsPage,
})
