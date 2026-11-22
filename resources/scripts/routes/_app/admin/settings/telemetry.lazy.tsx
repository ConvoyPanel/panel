import {
    telemetrySettingsQuery,
    telemetrySettingsSchema,
    updateTelemetrySettings,
    useTelemetrySettings,
} from '@/features/settings/api.ts'
import TelemetryReportDialog from '@/features/settings/components/TelemetryReportDialog.tsx'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck, IconCode } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardFooter } from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { SwitchForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

const TelemetrySettingsPage = () => {
    const { data: settings, isLoading } = useTelemetrySettings()
    const mutateSettings = useQueryMutator(telemetrySettingsQuery().queryKey)
    const [reportOpen, setReportOpen] = useState(false)

    const form = useForm<z.infer<typeof telemetrySettingsSchema>>({
        resolver: zodResolver(telemetrySettingsSchema),
        defaultValues: { enabled: true },
    })

    useEffect(() => {
        if (!settings) return

        form.reset({ enabled: settings.enabled })
    }, [form, settings])

    const { mutateAsync: save } = useMutation({
        mutationFn: updateTelemetrySettings,
        onSuccess: async updated => {
            await mutateSettings(() => updated)
            toast.add({ title: 'Telemetry settings updated', type: 'success' })
        },
    })

    const submit = async (data: z.infer<typeof telemetrySettingsSchema>) => {
        try {
            await save(data)
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({
                title: 'Failed to update telemetry settings',
                type: 'error',
            })
            console.error(e)
        }
    }

    if (isLoading) {
        return (
            <>
                <Heading>Telemetry</Heading>
                <Skeleton className={'h-40'} />
            </>
        )
    }

    const disabledBy = settings?.disabledBy ?? null

    return (
        <div className={'@container space-y-4'}>
            <Heading>Telemetry</Heading>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit)}>
                    <Card>
                        <CardContent className={'pt-4'}>
                            <SwitchForm
                                name={'enabled'}
                                label={'Send a daily usage report'}
                                description={
                                    disabledBy
                                        ? `Turned off by ${disabledBy}.`
                                        : 'Counts of accounts, nodes, servers and page views, which features are on, and version numbers. No names, addresses or license key. Turning it off deletes the reports already sent.'
                                }
                                disabled={disabledBy !== null}
                            />
                        </CardContent>
                        <CardFooter className={'justify-end gap-2'}>
                            <Button
                                type={'button'}
                                variant={'outline'}
                                onClick={() => setReportOpen(true)}
                            >
                                View report <IconCode className={'size-4'} />
                            </Button>
                            <FormButton
                                className={'flex'}
                                disabled={disabledBy !== null}
                            >
                                Save changes <IconCheck className={'size-4'} />
                            </FormButton>
                        </CardFooter>
                    </Card>
                </form>
            </Form>
            <TelemetryReportDialog
                open={reportOpen}
                onOpenChange={setReportOpen}
            />
        </div>
    )
}

export const Route = createLazyFileRoute('/_app/admin/settings/telemetry')({
    component: TelemetrySettingsPage,
})
