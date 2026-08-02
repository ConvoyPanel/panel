import {
    anchorSettingsQuery,
    anchorSettingsSchema,
    updateAnchorSettings,
    useAnchorSettings,
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

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/settings/anchor')({
    component: AnchorSettingsPage,
})

function AnchorSettingsPage() {
    const { data: settings, isLoading } = useAnchorSettings()
    const mutateSettings = useQueryMutator(anchorSettingsQuery().queryKey)

    const form = useForm<z.infer<typeof anchorSettingsSchema>>({
        resolver: zodResolver(anchorSettingsSchema),
        defaultValues: { panelUrl: '' },
    })

    useEffect(() => {
        if (!settings) return

        form.reset({ panelUrl: settings.panelUrl ?? '' })
    }, [form, settings])

    const { mutateAsync: save } = useMutation({
        mutationFn: updateAnchorSettings,
        onSuccess: async updated => {
            await mutateSettings(() => updated)
            toast.add({ title: 'Anchor settings updated', type: 'success' })
        },
    })

    const submit = async (data: z.infer<typeof anchorSettingsSchema>) => {
        try {
            await save(data)
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({
                title: 'Failed to update Anchor settings',
                type: 'error',
            })
            console.error(e)
        }
    }

    if (isLoading) {
        return (
            <>
                <Heading>Anchor</Heading>
                <Skeleton className={'h-64'} />
            </>
        )
    }

    return (
        <div className={'@container space-y-4'}>
            <Heading>Anchor</Heading>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Panel URL</CardTitle>
                            <CardDescription>
                                Where Anchors reach this panel, for enrollment
                                and heartbeats. Leave blank to use the panel's
                                own address — set it when that address does not
                                resolve on the network your Anchors run in. An
                                individual Anchor can still override this.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <InputForm
                                name={'panelUrl'}
                                label={'Panel URL'}
                                placeholder={'https://panel.example.com'}
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
