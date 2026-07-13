import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Node } from '@/types/node.ts'
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
    nodeQueries,
    nodeUpdateSchema,
    updateNode,
    useNode,
} from '@/features/nodes/api.ts'
import LocationPicker from '@/features/locations/components/LocationPicker.tsx'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { CheckboxForm, InputForm } from '@/components/ui/Forms'
import { Form, FormButton } from '@/components/ui/Form'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/settings')({
    component: NodeSettings,
})

const mebibytes = 1024 * 1024

const defaultsFromNode = (node: Node): z.input<typeof nodeUpdateSchema> =>
    ({
        displayName: node.displayName,
        locationId: String(node.locationId),
        fqdn: node.fqdn,
        port: String(node.port),
        name: node.name,
        verifyTls: node.verifyTls,
        tokenId: '',
        tokenSecret: '',
        socketCount: String(node.socketCount),
        coreCount: String(node.coreCount),
        cpuCount: String(node.cpuCount),
        memory: String(Math.round(node.memory / mebibytes)),
        memoryOverallocate: String(node.memoryOverallocate),
    }) as unknown as z.input<typeof nodeUpdateSchema>

function NodeSettings() {
    const { nodeId } = Route.useParams()
    const numericNodeId = Number(nodeId)
    const { data: node, isLoading } = useNode(numericNodeId)
    const mutateNode = useQueryMutator<Node>(
        nodeQueries.detail(numericNodeId).queryKey
    )
    const mutateNodeLists = useQueryMutator(nodeQueries.lists())

    const form = useForm<z.input<typeof nodeUpdateSchema>>({
        resolver: zodResolver(nodeUpdateSchema),
    })

    useEffect(() => {
        if (node) form.reset(defaultsFromNode(node))
    }, [form, node])

    const { mutateAsync: save } = useMutation({
        mutationFn: (payload: z.infer<typeof nodeUpdateSchema>) =>
            updateNode(numericNodeId, payload),
        onSuccess: async updatedNode => {
            await mutateNode(() => updatedNode)
            await mutateNodeLists()
            toast.success('Node updated')
        },
    })

    const submit = async ({
        memory,
        ...data
    }: z.infer<typeof nodeUpdateSchema>) => {
        try {
            await save({
                ...data,
                memory: memory * mebibytes,
            })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to update node')
            console.error(e)
        }
    }

    if (isLoading || !node) {
        return (
            <>
                <Heading>Settings</Heading>
                <Skeleton className={'h-96'} />
            </>
        )
    }

    return (
        <div className={'@container space-y-4'}>
            <Heading>Settings</Heading>
            <Form {...form}>
                <form onSubmit={form.handleSubmit(submit as any)}>
                    <div className={'grid grid-cols-1 gap-4 @xl:grid-cols-2'}>
                        <Card>
                            <CardHeader>
                                <CardTitle>General</CardTitle>
                                <CardDescription>
                                    Rename the node and move it between
                                    locations.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className={'space-y-4'}>
                                <InputForm
                                    name={'displayName'}
                                    label={'Display Name'}
                                />
                                <LocationPicker />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Connection</CardTitle>
                                <CardDescription>
                                    Update Proxmox API connection details. Leave
                                    token fields blank to keep the existing token.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className={'space-y-4'}>
                                <div
                                    className={
                                        'grid grid-cols-[1fr_6rem] gap-3'
                                    }
                                >
                                    <InputForm name={'fqdn'} label={'FQDN'} />
                                    <InputForm
                                        name={'port'}
                                        label={'Port'}
                                        type={'number'}
                                    />
                                </div>
                                <InputForm
                                    name={'name'}
                                    label={'Proxmox Node Name'}
                                />
                                <InputForm
                                    name={'tokenId'}
                                    label={'Token ID'}
                                    autoComplete={'off'}
                                />
                                <InputForm
                                    name={'tokenSecret'}
                                    label={'Token Secret'}
                                    type={'password'}
                                    autoComplete={'off'}
                                />
                                <CheckboxForm
                                    name={'verifyTls'}
                                    label={'Verify TLS Certificate'}
                                    description={
                                        'Only disable TLS verification when this node is reachable through a trusted private path.'
                                    }
                                />
                            </CardContent>
                        </Card>

                        <Card className={'@xl:col-span-2'}>
                            <CardHeader>
                                <CardTitle>Specifications</CardTitle>
                                <CardDescription>
                                    These values drive Convoy capacity checks and
                                    placement decisions.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className={'space-y-4'}>
                                <div
                                    className={
                                        'grid grid-cols-1 gap-3 @md:grid-cols-3'
                                    }
                                >
                                    <InputForm
                                        name={'socketCount'}
                                        label={'Sockets'}
                                        type={'number'}
                                    />
                                    <InputForm
                                        name={'coreCount'}
                                        label={'Cores'}
                                        type={'number'}
                                    />
                                    <InputForm
                                        name={'cpuCount'}
                                        label={'CPUs'}
                                        type={'number'}
                                    />
                                </div>
                                <div
                                    className={
                                        'grid grid-cols-1 gap-3 @md:grid-cols-2'
                                    }
                                >
                                    <InputForm
                                        name={'memory'}
                                        label={'Memory (MiB)'}
                                        type={'number'}
                                    />
                                    <InputForm
                                        name={'memoryOverallocate'}
                                        label={'Memory Overallocate (%)'}
                                        type={'number'}
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className={'mt-4 flex justify-end'}>
                        <FormButton size={'sm'} className={'flex'}>
                            Save changes <IconCheck className={'size-4'} />
                        </FormButton>
                    </div>
                </form>
            </Form>
        </div>
    )
}
