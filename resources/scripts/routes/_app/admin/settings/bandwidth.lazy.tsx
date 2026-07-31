import PenaltyActionFields from '@/features/bandwidth/components/PenaltyActionFields.tsx'
import { BYTES_PER_MB } from '@/features/bandwidth/overage-penalty.ts'
import {
    bandwidthSettingsSchema,
    settingsQueries,
    updateBandwidthSettings,
    useBandwidthSettings,
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
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/settings/bandwidth')({
    component: BandwidthSettingsPage,
})

function BandwidthSettingsPage() {
    const { data: settings, isLoading } = useBandwidthSettings()
    const mutateSettings = useQueryMutator(settingsQueries.bandwidth().queryKey)

    const form = useForm<z.input<typeof bandwidthSettingsSchema>>({
        resolver: zodResolver(bandwidthSettingsSchema),
    })

    useEffect(() => {
        if (!settings) return

        form.reset({
            overagePenaltyMode: 'custom',
            overagePenaltyAction: settings.overagePenalty.action,
            overagePenaltyRate:
                settings.overagePenalty.rate != null
                    ? String(settings.overagePenalty.rate / BYTES_PER_MB)
                    : '',
        })
    }, [form, settings])

    const { mutateAsync: save } = useMutation({
        mutationFn: updateBandwidthSettings,
        onSuccess: async updated => {
            await mutateSettings(() => updated)
            toast.add({ title: 'Bandwidth settings updated', type: 'success' })
        },
    })

    const submit = async (data: z.infer<typeof bandwidthSettingsSchema>) => {
        try {
            await save(data)
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({
                title: 'Failed to update bandwidth settings',
                type: 'error',
            })
            console.error(e)
        }
    }

    if (isLoading || !settings) {
        return (
            <>
                <Heading>Bandwidth</Heading>
                <Skeleton className={'h-64'} />
            </>
        )
    }

    return (
        <div className={'@container space-y-4'}>
            <Heading>Bandwidth</Heading>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit as any)}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Overage penalty</CardTitle>
                            <CardDescription>
                                The default for every server, used unless its
                                node or the server itself overrides it.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <PenaltyActionFields />
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
