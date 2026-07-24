import OveragePenaltyFields from '@/features/bandwidth/components/OveragePenaltyFields.tsx'
import { overagePenaltyDefaults } from '@/features/bandwidth/overage-penalty.ts'
import {
    nodeQueries,
    nodeUpdateSchema,
    updateNode,
    useNode,
} from '@/features/nodes/api.ts'
import NodeFormToolbar from '@/features/nodes/components/NodeFormToolbar.tsx'
import NodeStatusIndicator from '@/features/nodes/components/NodeStatusIndicator.tsx'
import ConnectionSection from '@/features/nodes/components/sections/ConnectionSection.tsx'
import GeneralSection from '@/features/nodes/components/sections/GeneralSection.tsx'
import SpecificationsSection from '@/features/nodes/components/sections/SpecificationsSection.tsx'
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

import { Button } from '@/components/ui/Button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import Skeleton from '@/components/ui/Skeleton.tsx'

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
        ...overagePenaltyDefaults(node.overagePenalty),
        anchorId: node.anchorId?.toString() ?? 'none',
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
            <div className={'mx-auto w-full max-w-4xl space-y-4'}>
                <Skeleton className={'h-14'} />
                <Skeleton className={'h-96'} />
            </div>
        )
    }

    const { isDirty } = form.formState

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(submit as any)}>
                {/* Capped so the fields keep a readable measure: AppLayout gives
                    the page up to 1600px, and a form stretched that far pulls
                    each label away from its own control. */}
                <div className={'mx-auto w-full max-w-4xl'}>
                    <NodeFormToolbar
                        title={'Settings'}
                        subtitle={
                            <span className={'flex items-center gap-2'}>
                                <span className={'truncate'}>
                                    {node.fqdn}:{node.port}
                                </span>
                                <span aria-hidden className={'opacity-40'}>
                                    •
                                </span>
                                {/* Reads `node.status`, already on the record
                                    this page loads — no extra request — so the
                                    connection test below reads as confirmation
                                    rather than the only signal on the page. */}
                                <NodeStatusIndicator node={node} />
                            </span>
                        }
                        actions={
                            <>
                                {/* Only offered once there is something to
                                    discard, so the bar stays quiet at rest. */}
                                {isDirty && (
                                    <Button
                                        type={'button'}
                                        variant={'ghost'}
                                        onClick={() =>
                                            form.reset(defaultsFromNode(node))
                                        }
                                    >
                                        Discard
                                    </Button>
                                )}
                                <FormButton
                                    className={'flex'}
                                    disabled={!isDirty}
                                >
                                    Save changes{' '}
                                    <IconCheck className={'size-4'} />
                                </FormButton>
                            </>
                        }
                    />

                    <div className={'space-y-4 pt-4'}>
                        <GeneralSection />
                        <ConnectionSection
                            mode={'edit'}
                            nodeId={numericNodeId}
                        />
                        <SpecificationsSection />

                        <Card>
                            <CardHeader>
                                <CardTitle>Bandwidth</CardTitle>
                                <CardDescription>
                                    Applied when a server on this node passes
                                    its monthly quota. A server can override it.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <OveragePenaltyFields
                                    inheritedFrom={node.defaultOveragePenalty}
                                    inheritedLabel={'global'}
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </Form>
    )
}
