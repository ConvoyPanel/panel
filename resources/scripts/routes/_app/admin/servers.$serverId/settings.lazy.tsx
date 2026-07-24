import OveragePenaltyFields from '@/features/bandwidth/components/OveragePenaltyFields.tsx'
import {
    BYTES_PER_MB,
    overagePenaltyDefaults,
} from '@/features/bandwidth/overage-penalty.ts'
import {
    serverBuildSchema,
    serverQueries,
    updateServerBuild,
    useServer,
} from '@/features/servers/admin/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Server } from '@/types/server.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
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
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute(
    '/_app/admin/servers/$serverId/settings'
)({
    component: ServerBuildSettings,
})

const MEBIBYTE = 1024 * 1024

const defaultsFromServer = (
    server: Server
): z.input<typeof serverBuildSchema> => ({
    cpu: String(server.cpu),
    memory: String(server.memory / MEBIBYTE),
    disk: String(server.disk / MEBIBYTE),
    backupCountLimit: String(server.backup.countLimit),
    backupSizeLimit: String(
        server.backup.sizeLimit === -1 ? -1 : server.backup.sizeLimit / MEBIBYTE
    ),
    bandwidthLimit: String(
        server.bandwidth.limit === -1 ? -1 : server.bandwidth.limit / MEBIBYTE
    ),
    bandwidthUsage: String(server.bandwidth.usage / MEBIBYTE),
    speedLimit:
        server.bandwidth.speedLimit == null
            ? ''
            : String(server.bandwidth.speedLimit / BYTES_PER_MB),
    ...overagePenaltyDefaults(server.bandwidth.overagePenalty),
})

function ServerBuildSettings() {
    const { serverId } = Route.useParams()
    const numericServerId = Number(serverId)
    const { data: server, isLoading } = useServer(numericServerId)
    const mutateServer = useQueryMutator<Server>(
        serverQueries.detail(numericServerId).queryKey
    )
    const mutateServerLists = useQueryMutator(serverQueries.lists())

    const form = useForm<z.input<typeof serverBuildSchema>>({
        resolver: zodResolver(serverBuildSchema),
    })

    useEffect(() => {
        if (server) form.reset(defaultsFromServer(server))
    }, [form, server])

    const { mutateAsync: save } = useMutation({
        mutationFn: (payload: z.infer<typeof serverBuildSchema>) =>
            updateServerBuild(numericServerId, payload),
    })

    const submit = async (payload: z.infer<typeof serverBuildSchema>) => {
        try {
            const updatedServer = await save(payload)
            await mutateServer(() => updatedServer, false)
            await mutateServerLists()
            form.reset(defaultsFromServer(updatedServer))
            toast.success('Server limits updated')
        } catch (error) {
            handleFormErrors(error, form.setError, {
                'backup_count_limit': 'backupCountLimit',
                'backup_size_limit': 'backupSizeLimit',
                'bandwidth_limit': 'bandwidthLimit',
                'bandwidth_usage': 'bandwidthUsage',
                'speed_limit': 'speedLimit',
                'overage_penalty': 'overagePenaltyMode',
                'overage_penalty.action': 'overagePenaltyAction',
                'overage_penalty.rate': 'overagePenaltyRate',
            })
            toast.error('Failed to update server limits')
            console.error(error)
        }
    }

    if (isLoading || !server) {
        return (
            <>
                <Heading>Build & limits</Heading>
                <Skeleton className={'h-96'} />
            </>
        )
    }

    const inheritedPenalty =
        server.node?.overagePenalty ??
        server.node?.defaultOveragePenalty ??
        null

    return (
        <div className={'@container space-y-4'}>
            <Heading>Build & limits</Heading>

            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit as any)}>
                    <div className={'grid grid-cols-1 gap-4 @xl:grid-cols-2'}>
                        <Card>
                            <CardHeader>
                                <CardTitle>Resources</CardTitle>
                                <CardDescription>
                                    Compute and primary-disk capacity assigned
                                    to this server.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className={'space-y-4'}>
                                <InputForm
                                    name={'cpu'}
                                    label={'vCPU'}
                                    type={'number'}
                                    min={1}
                                />
                                <div
                                    className={
                                        'grid grid-cols-1 gap-3 @md:grid-cols-2'
                                    }
                                >
                                    <InputForm
                                        name={'memory'}
                                        label={'Memory (MiB)'}
                                        type={'number'}
                                        min={16}
                                    />
                                    <InputForm
                                        name={'disk'}
                                        label={'Primary Disk (MiB)'}
                                        type={'number'}
                                        min={1}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Backups</CardTitle>
                                <CardDescription>
                                    Limit how many backups this server can
                                    retain and how much storage they can
                                    consume.
                                </CardDescription>
                            </CardHeader>
                            <CardContent
                                className={
                                    'grid grid-cols-1 gap-3 @md:grid-cols-2'
                                }
                            >
                                <InputForm
                                    name={'backupCountLimit'}
                                    label={'Backup Count'}
                                    type={'number'}
                                    min={-1}
                                    description={'Use -1 for unlimited.'}
                                />
                                <InputForm
                                    name={'backupSizeLimit'}
                                    label={'Storage Limit (MiB)'}
                                    type={'number'}
                                    min={-1}
                                    description={'Use -1 for unlimited.'}
                                />
                            </CardContent>
                        </Card>

                        <Card className={'@xl:col-span-2'}>
                            <CardHeader>
                                <CardTitle>Bandwidth</CardTitle>
                                <CardDescription>
                                    Configure the monthly quota, persistent NIC
                                    speed cap, and what happens when the quota
                                    is exhausted.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className={'space-y-4'}>
                                <div
                                    className={
                                        'grid grid-cols-1 gap-3 @md:grid-cols-3'
                                    }
                                >
                                    <InputForm
                                        name={'bandwidthLimit'}
                                        label={'Monthly Quota (MiB)'}
                                        type={'number'}
                                        min={-1}
                                        description={'Use -1 for unlimited.'}
                                    />
                                    <InputForm
                                        name={'bandwidthUsage'}
                                        label={'Current Usage (MiB)'}
                                        type={'number'}
                                        min={0}
                                    />
                                    <InputForm
                                        name={'speedLimit'}
                                        label={'Speed Limit (MB/s)'}
                                        type={'number'}
                                        min={1}
                                        step={'any'}
                                        description={
                                            'Caps every NIC. Leave blank for uncapped.'
                                        }
                                    />
                                </div>

                                <div className={'border-t pt-4'}>
                                    <OveragePenaltyFields
                                        inheritedFrom={inheritedPenalty}
                                        inheritedLabel={'node'}
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className={'mt-4 flex justify-end'}>
                        <FormButton
                            className={'flex'}
                            disabled={!form.formState.isDirty}
                        >
                            Save changes <IconCheck className={'size-4'} />
                        </FormButton>
                    </div>
                </form>
            </Form>
        </div>
    )
}
